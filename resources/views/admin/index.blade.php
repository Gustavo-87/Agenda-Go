<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Administración SaaS
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Registrar negocio</h3>
                    <form method="POST" action="{{ route('admin.businesses.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        @csrf
                        <x-text-input name="name" placeholder="Nombre" class="w-full" required />
                        <x-text-input name="slug" placeholder="Identificador (slug)" class="w-full" required />
                        <x-text-input name="sector" placeholder="Sector" class="w-full" />
                        <x-text-input name="phone" placeholder="Teléfono" class="w-full" />
                        <x-primary-button class="justify-center">Registrar</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Negocios y membresías</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Negocio</th>
                                    <th class="py-2 pr-4">Estado</th>
                                    <th class="py-2 pr-4">Membresía vigente</th>
                                    <th class="py-2 pr-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($negocios as $item)
                                    @php $membresia = $item->memberships->sortByDesc('ends_on')->first(); @endphp
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->slug }}</div>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $item->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $item->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            @if ($membresia)
                                                {{ $membresia->starts_on }} → {{ $membresia->ends_on }}
                                                <span class="text-xs text-gray-500">({{ $membresia->status }})</span>
                                            @else
                                                <span class="text-gray-500">Sin membresía</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            <div class="flex flex-wrap gap-2">
                                                @unless ($item->active)
                                                    <form method="POST" action="{{ route('admin.businesses.activate', $item) }}">
                                                        @csrf
                                                        <x-secondary-button type="submit">Activar</x-secondary-button>
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
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Registrar membresía</h3>
                    <form method="POST" action="{{ route('admin.memberships.store', ['business' => $negocios->first()?->id ?? 0]) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3"
                        id="form-membresia">
                        @csrf
                        <select name="business_id" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.action = '/admin/businesses/' + this.value + '/memberships'">
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

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pagos de membresía pendientes de verificación</h3>
                    @if ($pagosPendientes->isEmpty())
                        <p class="text-sm text-gray-500">Sin pagos pendientes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Negocio</th>
                                        <th class="py-2 pr-4">Monto</th>
                                        <th class="py-2 pr-4">Fecha de pago</th>
                                        <th class="py-2 pr-4">Referencia</th>
                                        <th class="py-2 pr-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($pagosPendientes as $pago)
                                        <tr>
                                            <td class="py-2 pr-4">{{ $pago->business?->name }}</td>
                                            <td class="py-2 pr-4">${{ number_format($pago->amount, 0, ',', '.') }}</td>
                                            <td class="py-2 pr-4">{{ $pago->paid_on }}</td>
                                            <td class="py-2 pr-4">{{ $pago->reference ?? '—' }}</td>
                                            <td class="py-2 pr-4">
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
