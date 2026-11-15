<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ficha de cliente
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $cliente->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Teléfono: {{ $cliente->phone }}</p>
                        <p class="text-sm text-gray-500">
                            Ausencias registradas:
                            <span class="font-medium {{ $cliente->absences >= 2 ? 'text-red-600' : 'text-gray-800' }}">{{ $cliente->absences }}</span>
                        </p>
                        <p class="text-sm text-gray-500">
                            Tratamiento de datos:
                            <span class="font-medium {{ $cliente->data_consent ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $cliente->data_consent ? 'autorizado '.($cliente->data_consent_at?->format('Y-m-d') ?? '') : 'pendiente de autorización' }}
                            </span>
                        </p>
                    </div>

                    <form method="POST" action="{{ route('customers.consent', $cliente) }}">
                        @csrf
                        <input type="hidden" name="data_consent" value="{{ $cliente->data_consent ? 0 : 1 }}">
                        <x-primary-button>
                            {{ $cliente->data_consent ? 'Revocar autorización' : 'Registrar autorización' }}
                        </x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Historial de citas</h3>

                    @if ($historial->isEmpty())
                        <p class="text-sm text-gray-500">Sin citas registradas.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Fecha</th>
                                        <th class="py-2 pr-4">Profesional</th>
                                        <th class="py-2 pr-4">Servicio</th>
                                        <th class="py-2 pr-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($historial as $cita)
                                        <tr>
                                            <td class="py-2 pr-4">{{ $cita->starts_at }}</td>
                                            <td class="py-2 pr-4">{{ $cita->professional?->name }}</td>
                                            <td class="py-2 pr-4">{{ $cita->service?->name }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                    {{ ['confirmed' => 'bg-emerald-100 text-emerald-800', 'completed' => 'bg-teal-100 text-teal-800', 'cancelled' => 'bg-gray-100 text-gray-600', 'absent' => 'bg-red-100 text-red-800', 'pending' => 'bg-amber-100 text-amber-800', 'expired' => 'bg-gray-100 text-gray-500'][$cita->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $cita->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('clientes.index') }}" class="inline-block text-sm text-teal-700 font-medium hover:underline">
                ← Volver a clientes
            </a>
        </div>
    </div>
</x-app-layout>
