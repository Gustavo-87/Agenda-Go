<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Ficha de cliente</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6 flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center text-xl font-extrabold">
                            {{ strtoupper(substr($cliente->name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">{{ $cliente->name }}</h3>
                            <p class="text-sm text-gray-500">Teléfono: {{ $cliente->phone }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cliente->absences >= 2 ? 'bg-red-100 text-red-700' : 'bg-warm-100 text-gray-600' }}">
                                    {{ $cliente->absences }} ausencias
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cliente->data_consent ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    Datos: {{ $cliente->data_consent ? 'autorizados '.($cliente->data_consent_at?->format('Y-m-d') ?? '') : 'pendientes' }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cliente->commercial_consent ? 'bg-turquoise-100 text-turquoise-700' : 'bg-gray-200 text-gray-600' }}">
                                    Comercial: {{ $cliente->commercial_consent ? 'autorizado '.($cliente->commercial_consent_at?->format('Y-m-d') ?? '') : 'no autorizado' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('customers.consent', $cliente) }}">
                        @csrf
                        <input type="hidden" name="data_consent" value="{{ $cliente->data_consent ? 0 : 1 }}">
                        <x-primary-button>
                            {{ $cliente->data_consent ? 'Revocar autorización' : 'Registrar autorización' }}
                        </x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('customers.commercial-consent', $cliente) }}">
                        @csrf
                        <input type="hidden" name="commercial_consent" value="{{ $cliente->commercial_consent ? 0 : 1 }}">
                        <x-secondary-button>
                            {{ $cliente->commercial_consent ? 'Revocar consentimiento comercial' : 'Autorizar comunicaciones comerciales' }}
                        </x-secondary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Historial de citas</h3>

                    @if ($historial->isEmpty())
                        <x-empty-state icon="calendar" title="Sin citas registradas" message="El historial se llenará con cada reserva atendida." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Fecha</th>
                                        <th class="py-3 px-4">Profesional</th>
                                        <th class="py-3 px-4">Servicio</th>
                                        <th class="py-3 px-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($historial as $cita)
                                        <tr>
                                            <td class="py-3 px-4">{{ \App\Support\FormatoHora::fechaHora($cita->starts_at) }}</td>
                                            <td class="py-3 px-4">{{ $cita->professional?->name }}</td>
                                            <td class="py-3 px-4">{{ $cita->service?->name }}</td>
                                            <td class="py-3 px-4"><x-badge :status="$cita->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('clientes.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-turquoise-700 hover:underline">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver a clientes
            </a>
        </div>
    </div>
</x-app-layout>
