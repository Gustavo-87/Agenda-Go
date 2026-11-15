<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Administración SaaS</h2>
        <p class="text-sm text-gray-500 mt-0.5">Negocios, membresías y verificación de pagos.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Registrar negocio</h3>
                    <form method="POST" action="{{ route('admin.businesses.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        @csrf
                        <x-text-input name="name" placeholder="Nombre" class="w-full" required />
                        <x-text-input name="slug" placeholder="Identificador (slug)" class="w-full" required />
                        <x-text-input name="sector" placeholder="Sector" class="w-full" />
                        <x-text-input name="phone" placeholder="Teléfono" class="w-full" />
                        <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Registrar</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Negocios y membresías</h3>
                    @if ($negocios->isEmpty())
                        <x-empty-state icon="briefcase" title="Sin negocios registrados" message="Registra el primer negocio para comenzar." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Negocio</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 px-4">Membresía vigente</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($negocios as $item)
                                        @php $membresia = $item->memberships->sortByDesc('ends_on')->first(); @endphp
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-petrol">{{ $item->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->slug }}</div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $item->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                    {{ $item->active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if ($membresia)
                                                    {{ $membresia->starts_on }} → {{ $membresia->ends_on }}
                                                    <span class="text-xs text-gray-500">({{ $membresia->status }})</span>
                                                @else
                                                    <span class="text-gray-500">Sin membresía</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex flex-wrap gap-2">
                                                    @unless ($item->active)
                                                        <form method="POST" action="{{ route('admin.businesses.activate', $item) }}">
                                                            @csrf
                                                            <x-primary-button type="submit">Activar</x-primary-button>
                                                        </form>
                                                    @endunless
                                                    @if ($item->active)
                                                        <form method="POST" action="{{ route('admin.businesses.suspend', $item) }}" onsubmit="return confirm('¿Suspender el negocio? Se bloquearán nuevas reservas.')">
                                                            @csrf
                                                            <x-danger-button type="submit">Suspender</x-danger-button>
                                                        </form>
                                                    @endif
                                                    @if ($membresia)
                                                        <form method="POST" action="{{ route('admin.memberships.renew', $membresia) }}" class="flex gap-2 items-center">
                                                            @csrf
                                                            <x-text-input name="amount" type="number" placeholder="Monto" class="w-24" required />
                                                            <x-text-input name="paid_on" type="date" class="w-36" required />
                                                            <x-secondary-button type="submit">Renovar</x-secondary-button>
                                                        </form>
                                                    @endif
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
                    <h3 class="text-lg font-bold mb-4">Registrar membresía</h3>
                    <form method="POST" action="{{ route('admin.memberships.store', ['business' => $negocios->first()?->id ?? 0]) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                        @csrf
                        <select name="business_id" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" onchange="this.form.action = '/admin/businesses/' + this.value + '/memberships'">
                            @foreach ($negocios as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="starts_on" type="date" class="w-full" required />
                        <x-text-input name="ends_on" type="date" class="w-full" required />
                        <x-text-input name="amount" type="number" placeholder="Monto" class="w-full" required />
                        <x-text-input name="paid_on" type="date" class="w-full" required />
                        <x-primary-button class="justify-center">Registrar</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Pagos de membresía pendientes de verificación</h3>
                    @if ($pagosPendientes->isEmpty())
                        <x-empty-state icon="money" title="Sin pagos pendientes" message="Los pagos por verificar aparecerán aquí." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Negocio</th>
                                        <th class="py-3 px-4">Monto</th>
                                        <th class="py-3 px-4">Fecha de pago</th>
                                        <th class="py-3 px-4">Referencia</th>
                                        <th class="py-3 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($pagosPendientes as $pago)
                                        <tr>
                                            <td class="py-3 px-4 font-semibold text-petrol">{{ $pago->business?->name }}</td>
                                            <td class="py-3 px-4">${{ number_format($pago->amount, 0, ',', '.') }}</td>
                                            <td class="py-3 px-4">{{ $pago->paid_on }}</td>
                                            <td class="py-3 px-4">{{ $pago->reference ?? '—' }}</td>
                                            <td class="py-3 px-4">
                                                <form method="POST" action="{{ route('admin.payments.verify', $pago) }}">
                                                    @csrf
                                                    <x-primary-button type="submit">Verificar y habilitar</x-primary-button>
                                                </form>
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
