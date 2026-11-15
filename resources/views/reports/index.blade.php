<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Reportes {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Indicadores operativos, pagos y exportaciones.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($negocio === null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    @if (auth()->user()->isSuperAdmin())
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Selecciona un negocio</h3>
                            <p class="text-sm text-gray-500 mb-4">Elige el negocio cuyos reportes deseas consultar.</p>
                            <x-business-selector :negocios="$negocios" />
                        </div>
                    @else
                        <x-empty-state icon="money" title="No tienes un negocio asignado"
                            message="Los reportes estarán disponibles cuando tu cuenta sea vinculada a un negocio." />
                    @endif
                </div>
            @else

                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-5 flex flex-wrap items-end gap-3">
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
                            <a href="{{ route('reports.appointments', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">CSV</a>
                            <a href="{{ route('reports.appointments.xlsx', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">Excel</a>
                            <a href="{{ route('reports.appointments.pdf', [$negocio, 'from' => $desde, 'to' => $hasta]) }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">PDF</a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-stat title="Ingresos por servicios" value="${{ number_format($resumen['ingresos_servicios'], 0, ',', '.') }}" icon="money" hint="Pagos verificados" />
                    <x-stat title="Ingresos por membresías" value="${{ number_format($resumen['ingresos_membresias'], 0, ',', '.') }}" icon="briefcase" hint="Registrados aparte" />
                    <div class="bg-white rounded-2xl border border-warm-200 shadow-card p-5">
                        <div class="text-sm font-medium text-gray-500">Citas del periodo</div>
                        <div class="flex flex-wrap gap-2 mt-3">
                            @forelse ($resumen['citas_por_estado'] as $estado => $total)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-warm-100 text-gray-700">{{ $estado }}: {{ $total }}</span>
                            @empty
                                <span class="text-sm text-gray-500">Sin citas.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Citas del periodo</h3>
                        @if ($citas->isEmpty())
                            <x-empty-state icon="calendar" title="Sin citas en el periodo" message="Ajusta el rango de fechas o registra nuevas reservas." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Fecha</th>
                                            <th class="py-3 px-4">Profesional</th>
                                            <th class="py-3 px-4">Servicio</th>
                                            <th class="py-3 px-4">Cliente</th>
                                            <th class="py-3 px-4">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($citas as $cita)
                                            <tr>
                                                <td class="py-3 px-4">{{ $cita->starts_at }}</td>
                                                <td class="py-3 px-4">{{ $cita->professional_name }}</td>
                                                <td class="py-3 px-4">{{ $cita->service_name }}</td>
                                                <td class="py-3 px-4">{{ $cita->customer_name }}</td>
                                                <td class="py-3 px-4"><x-badge :status="$cita->status" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4">{{ $citas->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Pagos de servicios</h3>

                        <form method="POST" action="{{ route('payments.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-6">
                            @csrf
                            <x-text-input name="amount" type="number" placeholder="Monto" class="w-full" required />
                            <x-text-input name="paid_on" type="date" class="w-full" required />
                            <x-text-input name="method" placeholder="Método" class="w-full" />
                            <x-text-input name="reference" placeholder="Referencia" class="w-full" />
                            <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Registrar</x-primary-button>
                        </form>

                        @if ($pagos->isEmpty())
                            <x-empty-state icon="money" title="Sin pagos registrados" message="Los pagos de servicios aparecerán aquí." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Fecha</th>
                                            <th class="py-3 px-4">Monto</th>
                                            <th class="py-3 px-4">Método</th>
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
                                                <td class="py-3 px-4">{{ $pago->method ?? '—' }}</td>
                                                <td class="py-3 px-4">{{ $pago->reference ?? '—' }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $pago->verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                        {{ $pago->verified ? 'Verificado' : 'Pendiente' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    @unless ($pago->verified)
                                                        <form method="POST" action="{{ route('payments.verify', $pago) }}">
                                                            @csrf
                                                            <x-secondary-button type="submit">Verificar</x-secondary-button>
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

            @endif
        </div>
    </div>
</x-app-layout>
