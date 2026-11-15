<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reportes — {{ $negocio->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 flex flex-wrap items-end gap-3">
                    <form method="GET" action="{{ route('reportes.index') }}" class="flex flex-wrap items-end gap-3">
                        @if (auth()->user()->isSuperAdmin())
                            <input type="hidden" name="business_id" value="{{ $negocio->id }}">
                        @endif
                        <div>
                            <x-input-label for="from" value="Desde" />
                            <x-text-input id="from" name="from" type="date" :value="$desde" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="to" value="Hasta" />
                            <x-text-input id="to" name="to" type="date" :value="$hasta" class="mt-1" />
                        </div>
                        <x-primary-button>Filtrar</x-primary-button>
                    </form>

                    <div class="flex gap-2 ms-auto">
                        <a href="{{ route('reports.appointments', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">CSV</a>
                        <a href="{{ route('reports.appointments.xlsx', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">Excel</a>
                        <a href="{{ route('reports.appointments.pdf', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">PDF</a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Ingresos por servicios</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1">${{ number_format($resumen['ingresos_servicios'], 0, ',', '.') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Ingresos por membresías</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1">${{ number_format($resumen['ingresos_membresias'], 0, ',', '.') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Citas del periodo</div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @forelse ($resumen['citas_por_estado'] as $estado => $total)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $estado }}: {{ $total }}</span>
                        @empty
                            <span class="text-sm text-gray-500">Sin citas.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Citas del periodo</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Fecha</th>
                                    <th class="py-2 pr-4">Profesional</th>
                                    <th class="py-2 pr-4">Servicio</th>
                                    <th class="py-2 pr-4">Cliente</th>
                                    <th class="py-2 pr-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($citas as $cita)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $cita->starts_at }}</td>
                                        <td class="py-2 pr-4">{{ $cita->professional_name }}</td>
                                        <td class="py-2 pr-4">{{ $cita->service_name }}</td>
                                        <td class="py-2 pr-4">{{ $cita->customer_name }}</td>
                                        <td class="py-2 pr-4">{{ $cita->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-3 text-gray-500">Sin citas en el periodo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pagos de servicios</h3>

                    <form method="POST" action="{{ route('payments.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-6">
                        @csrf
                        <x-text-input name="amount" type="number" placeholder="Monto" class="w-full" required />
                        <x-text-input name="paid_on" type="date" class="w-full" required />
                        <x-text-input name="method" placeholder="Método" class="w-full" />
                        <x-text-input name="reference" placeholder="Referencia" class="w-full" />
                        <x-primary-button class="justify-center">Registrar pago</x-primary-button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Fecha</th>
                                    <th class="py-2 pr-4">Monto</th>
                                    <th class="py-2 pr-4">Método</th>
                                    <th class="py-2 pr-4">Referencia</th>
                                    <th class="py-2 pr-4">Estado</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($pagos as $pago)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $pago->paid_on }}</td>
                                        <td class="py-2 pr-4">${{ number_format($pago->amount, 0, ',', '.') }}</td>
                                        <td class="py-2 pr-4">{{ $pago->method ?? '—' }}</td>
                                        <td class="py-2 pr-4">{{ $pago->reference ?? '—' }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pago->verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $pago->verified ? 'Verificado' : 'Pendiente' }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            @unless ($pago->verified)
                                                <form method="POST" action="{{ route('payments.verify', $pago) }}">
                                                    @csrf
                                                    <x-secondary-button type="submit">Verificar</x-secondary-button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-3 text-gray-500">Sin pagos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
