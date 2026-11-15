<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold">{{ $negocio->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">Detalle del negocio, administradores y membresías.</p>
            </div>
            <a href="{{ route('admin.pantalla') }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">
                ← Volver a negocios
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-bold">Datos del negocio</h3>
                        <div class="flex gap-2">
                            @unless ($negocio->active)
                                                                <form method="POST" action="{{ route('admin.businesses.activate', $negocio) }}">
                                    @csrf
                                    <x-primary-button type="submit">Activar</x-primary-button>
                                </form>
                            @endunless
                            @if ($negocio->active)
                                <form method="POST" action="{{ route('admin.businesses.suspend', $negocio) }}" onsubmit="return confirm('¿Suspender el negocio? Se bloquearán nuevas reservas.')">
                                    @csrf
                                    <x-danger-button type="submit">Suspender</x-danger-button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.businesses.update', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label value="Nombre" />
                            <x-text-input name="name" :value="$negocio->name" class="w-full mt-1" required />
                        </div>
                        <div>
                            <x-input-label value="Identificador (slug)" />
                            <x-text-input name="slug" :value="$negocio->slug" class="w-full mt-1" required />
                        </div>
                        <div>
                            <x-input-label value="Sector" />
                            <x-text-input name="sector" :value="$negocio->sector" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Correo" />
                            <x-text-input name="email" type="email" :value="$negocio->email" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Teléfono" />
                            <x-text-input name="phone" :value="$negocio->phone" class="w-full mt-1" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button>Guardar cambios</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1">Invitar usuarios</h3>
                    <p class="text-sm text-gray-500 mb-4">Recibirán un correo con un enlace temporal para crear su contraseña.</p>

                    <form method="POST" action="{{ route('invitations.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-6">
                        @csrf
                        <x-text-input name="email" type="email" placeholder="Correo electrónico" class="w-full" required />
                        <select name="role" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                            <option value="admin">Administrador</option>
                            <option value="professional">Profesional</option>
                        </select>
                        <div></div>
                        <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Enviar invitación</x-primary-button>
                    </form>

                    @if ($usuarios->isEmpty())
                        <x-empty-state icon="users" title="Sin usuarios" message="Crea el primer administrador para este negocio." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Usuario</th>
                                        <th class="py-3 px-4">Rol</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($usuarios as $usuario)
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-petrol">{{ $usuario->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $usuario->email }}</div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $usuario->role->value === 'admin' ? 'bg-turquoise-100 text-turquoise-700' : 'bg-warm-100 text-gray-600' }}">
                                                    {{ $usuario->role->value === 'admin' ? 'Administrador' : 'Profesional' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('admin.users.update', $usuario) }}" class="flex gap-2 items-center">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="role" class="border-warm-300 rounded-xl text-xs focus:border-turquoise-500 focus:ring-turquoise-500">
                                                            <option value="admin" @selected($usuario->role->value === 'admin')>Administrador</option>
                                                            <option value="professional" @selected($usuario->role->value === 'professional')>Profesional</option>
                                                        </select>
                                                        <x-secondary-button type="submit">Cambiar rol</x-secondary-button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.users.detach', $usuario) }}" onsubmit="return confirm('¿Desvincular al usuario del negocio?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit">Desvincular</x-danger-button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Membresías del negocio</h3>

                    <form method="POST" action="{{ route('admin.memberships.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-6">
                        @csrf
                        <x-text-input name="starts_on" type="date" class="w-full" required />
                        <x-text-input name="ends_on" type="date" class="w-full" required />
                        <x-text-input name="amount" type="number" placeholder="Monto" class="w-full" required />
                        <x-text-input name="paid_on" type="date" class="w-full" required />
                        <x-primary-button class="justify-center">Registrar membresía</x-primary-button>
                    </form>

                    @if ($negocio->memberships->isEmpty())
                        <x-empty-state icon="money" title="Sin membresías" message="Registra la primera membresía para habilitar el servicio." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Periodo</th>
                                        <th class="py-3 px-4">Monto</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($negocio->memberships->sortByDesc('ends_on') as $membresia)
                                        <tr>
                                            <td class="py-3 px-4">{{ $membresia->starts_on }} → {{ $membresia->ends_on }}</td>
                                            <td class="py-3 px-4">${{ number_format($membresia->amount, 0, ',', '.') }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $membresia->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                                    {{ $membresia->status === 'active' ? 'Activa' : 'Suspendida' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <form method="POST" action="{{ route('admin.memberships.renew', $membresia) }}" class="flex gap-2 items-center">
                                                    @csrf
                                                    <x-text-input name="amount" type="number" placeholder="Monto" class="w-24" required />
                                                    <x-text-input name="paid_on" type="date" class="w-36" required />
                                                    <x-secondary-button type="submit">Renovar</x-secondary-button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h4 class="text-sm font-bold text-petrol mt-6 mb-2">Invitaciones pendientes</h4>
                    @if ($invitaciones->isEmpty())
                        <p class="text-sm text-gray-500">Sin invitaciones pendientes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Correo</th>
                                        <th class="py-3 px-4">Rol</th>
                                        <th class="py-3 px-4">Vence</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($invitaciones as $invitacion)
                                        @php $vencida = $invitacion->expires_at->isPast(); @endphp
                                        <tr>
                                            <td class="py-3 px-4 font-semibold text-petrol">{{ $invitacion->email }}</td>
                                            <td class="py-3 px-4">{{ $invitacion->role === 'admin' ? 'Administrador' : 'Profesional' }}</td>
                                            <td class="py-3 px-4">{{ $invitacion->expires_at->format('d/m/Y H:i') }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $vencida ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $vencida ? 'Vencida' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('invitations.resend', $invitacion) }}">
                                                        @csrf
                                                        <x-secondary-button type="submit">Reenviar</x-secondary-button>
                                                    </form>
                                                    <form method="POST" action="{{ route('invitations.revoke', $invitacion) }}" onsubmit="return confirm('¿Revocar esta invitación? El enlace dejará de funcionar.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit">Revocar</x-danger-button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h4 class="text-sm font-bold text-petrol mt-6 mb-2">Pagos de membresía</h4>
                    @if ($pagos->isEmpty())
                        <p class="text-sm text-gray-500">Sin pagos registrados.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Fecha</th>
                                        <th class="py-3 px-4">Monto</th>
                                        <th class="py-3 px-4">Referencia</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($pagos as $pago)
                                        <tr>
                                            <td class="py-3 px-4">{{ $pago->paid_on }}</td>
                                            <td class="py-3 px-4">${{ number_format($pago->amount, 0, ',', '.') }}</td>
                                            <td class="py-3 px-4">{{ $pago->reference ?? '—' }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $pago->verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $pago->verified ? 'Verificado' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                @unless ($pago->verified)
                                                    <form method="POST" action="{{ route('admin.payments.verify', $pago) }}">
                                                        @csrf
                                                        <x-primary-button type="submit">Verificar y habilitar</x-primary-button>
                                                    </form>
                                                @endunless
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
