<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Agenda — {{ $date }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('agenda.index', ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()]) }}"
                       class="px-3 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ← Anterior
                    </a>
                    <form method="GET" action="{{ route('agenda.index') }}" class="flex items-center gap-2">
                        <x-text-input name="date" type="date" :value="$date" class="mt-0" />
                        <x-primary-button>Ver</x-primary-button>
                    </form>
                    <a href="{{ route('agenda.index', ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()]) }}"
                       class="px-3 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Siguiente →
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($appointments->isEmpty())
                        <p class="text-sm text-gray-500">No hay citas para este día.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Hora</th>
                                        <th class="py-2 pr-4">Cliente</th>
                                        <th class="py-2 pr-4">Servicio</th>
                                        <th class="py-2 pr-4">Estado</th>
                                        <th class="py-2 pr-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($appointments as $appointment)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-gray-800">
                                                {{ substr($appointment->starts_at, 11, 5) }}
                                            </td>
                                            <td class="py-3 pr-4">{{ $appointment->customer_name }}</td>
                                            <td class="py-3 pr-4">{{ $appointment->service_name }}</td>
                                            <td class="py-3 pr-4">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                    {{ ['confirmed' => 'bg-emerald-100 text-emerald-800', 'completed' => 'bg-teal-100 text-teal-800', 'cancelled' => 'bg-gray-100 text-gray-600', 'absent' => 'bg-red-100 text-red-800', 'pending' => 'bg-amber-100 text-amber-800', 'expired' => 'bg-gray-100 text-gray-500'][$appointment->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $appointment->status }}
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <div class="flex flex-wrap gap-2">
                                                    @if ($appointment->status === 'pending')
                                                        <form method="POST" action="{{ route('appointments.confirm', $appointment->id) }}">
                                                            @csrf
                                                            <x-primary-button type="submit">Confirmar</x-primary-button>
                                                        </form>
                                                    @endif

                                                    @if ($appointment->status === 'confirmed')
                                                        <form method="POST" action="{{ route('appointments.attend', $appointment->id) }}">
                                                            @csrf
                                                            <x-primary-button type="submit">Asistió</x-primary-button>
                                                        </form>
                                                        <form method="POST" action="{{ route('appointments.absent', $appointment->id) }}">
                                                            @csrf
                                                            <x-secondary-button type="submit">Ausente</x-secondary-button>
                                                        </form>
                                                    @endif

                                                    @if (in_array($appointment->status, ['pending', 'confirmed'], true))
                                                        <form method="POST" action="{{ route('appointments.reschedule', $appointment->id) }}"
                                                              class="flex gap-2 items-center"
                                                              onsubmit="this.starts_at.value = this.fecha.value + ' ' + this.hora.value + ':00'">
                                                            @csrf
                                                            <input type="hidden" name="starts_at" value="">
                                                            <x-text-input name="fecha" type="date" :value="$date" class="w-36" required />
                                                            <x-text-input name="hora" type="time" class="w-28" required />
                                                            <x-secondary-button type="submit">Reprogramar</x-secondary-button>
                                                        </form>
                                                        <form method="POST" action="{{ route('appointments.cancel', $appointment->id) }}"
                                                              onsubmit="return confirm('¿Cancelar esta reserva?')">
                                                            @csrf
                                                            <x-danger-button type="submit">Cancelar</x-danger-button>
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

        </div>
    </div>
</x-app-layout>
