<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Membresías</h2>
        <p class="text-sm text-gray-500 mt-0.5">Altas, renovaciones, pagos, estados y vencimientos.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-stat title="Membresías registradas" :value="$membresias->total()" icon="shield" />
                <x-stat title="Vencidas (en gracia o fuera)" :value="$vencidas" icon="bell" hint="Requieren renovación" />
                <x-stat title="Pagos por verificar" :value="$pagosPendientes->count()" icon="money" />
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1">Registro de membresía</h3>
                    <p class="text-sm text-gray-500 mb-4">Registra un nuevo período mensual y su pago. Los campos con <span class="text-red-600 font-semibold">*</span> son obligatorios.</p>

                    <form method="POST" action="{{ route('admin.memberships.store', ['business' => $negocios->first()?->id ?? 0]) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        onsubmit="this.action = '/admin/businesses/' + this.querySelector('[name=business_id]').value + '/memberships'">
                        @csrf
                        <div>
                            <x-input-label for="membresia-business" value="Negocio *" />
                            <select id="membresia-business" name="business_id" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" required>
                                @foreach ($negocios as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Negocio al que pertenece la membresía.</p>
                        </div>
                        <div>
                            <x-input-label for="membresia-starts" value="Fecha de inicio *" />
                            <x-text-input id="membresia-starts" name="starts_on" type="date" class="w-full mt-1" required />
                            <p class="text-xs text-gray-500 mt-1">Primer día del período mensual.</p>
                            <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="membresia-ends" value="Fecha de vencimiento *" />
                            <x-text-input id="membresia-ends" name="ends_on" type="date" class="w-full mt-1" required />
                            <p class="text-xs text-gray-500 mt-1">Último día del período (con 5 días de gracia).</p>
                            <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="membresia-amount" value="Valor mensual (COP) *" />
                            <x-text-input id="membresia-amount" name="amount" type="number" min="0" placeholder="Ej. 150000" class="w-full mt-1" required />
                            <p class="text-xs text-gray-500 mt-1">Valor de la membresía en pesos colombianos, sin decimales.</p>
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="membresia-paid" value="Fecha de pago *" />
                            <x-text-input id="membresia-paid" name="paid_on" type="date" class="w-full mt-1" required />
                            <p class="text-xs text-gray-500 mt-1">Fecha en que el negocio realizó el pago.</p>
                            <x-input-error :messages="$errors->get('paid_on')" class="mt-2" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button class="justify-center w-full">Registrar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Todas las membresías</h3>
                    @if ($membresias->isEmpty())
                        <x-empty-state icon="shield" title="Sin membresías" message="Las membresías registradas aparecerán aquí." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Negocio</th>
                                        <th class="py-3 px-4 hidden sm:table-cell">Periodo</th>
                                        <th class="py-3 px-4">Monto</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($membresias as $membresia)
                                        <tr class="hover:bg-warm-50">
                                            <td class="py-3 px-4">
                                                <a href="{{ route('admin.businesses.show', $membresia->business_id) }}" class="font-semibold text-turquoise-700 hover:underline">
                                                    {{ $membresia->business?->name }}
                                                </a>
                                            </td>
                                            <td class="py-3 px-4 hidden sm:table-cell">{{ $membresia->starts_on }} → {{ $membresia->ends_on }}</td>
                                            <td class="py-3 px-4">${{ number_format($membresia->amount, 0, ',', '.') }}</td>
                                            <td class="py-3 px-4">
                                                @php $vencida = $membresia->status === 'active' && $membresia->ends_on < now()->toDateString(); @endphp
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $membresia->status !== 'active' ? 'bg-red-100 text-red-700' : ($vencida ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                                    {{ $membresia->status !== 'active' ? 'Suspendida' : ($vencida ? 'Vencida' : 'Activa') }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <form method="POST" action="{{ route('admin.memberships.renew', $membresia) }}" class="flex gap-2 items-center">
                                                    @csrf
                                                    <x-text-input name="amount" type="number" placeholder="Valor (COP)" title="Valor mensual (COP)" class="w-24" required />
                                                    <x-text-input name="paid_on" type="date" title="Fecha de pago" class="w-36" required />
                                                    <x-secondary-button type="submit">Renovar</x-secondary-button>
                                                </form>
                                                @if ($membresia->status === 'active')
                                                    <form method="POST" action="{{ route('admin.memberships.cancel', $membresia) }}" class="mt-1" onsubmit="return confirm('¿Cancelar la membresía? El servicio se conservará hasta el fin del período pagado.')">
                                                        @csrf
                                                        <x-danger-button type="submit">Cancelar</x-danger-button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4">{{ $membresias->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Pagos pendientes de verificación</h3>
                    @if ($pagosPendientes->isEmpty())
                        <x-empty-state icon="money" title="Sin pagos pendientes" message="Los pagos por verificar aparecerán aquí." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Negocio</th>
                                        <th class="py-3 px-4">Monto</th>
                                        <th class="py-3 px-4">Fecha</th>
                                        <th class="py-3 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($pagosPendientes as $pago)
                                        <tr>
                                            <td class="py-3 px-4 font-semibold text-petrol">{{ $pago->business?->name }}</td>
                                            <td class="py-3 px-4">${{ number_format($pago->amount, 0, ',', '.') }}</td>
                                            <td class="py-3 px-4">{{ $pago->paid_on }}</td>
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
