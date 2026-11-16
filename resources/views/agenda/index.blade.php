<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold">Agenda</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $vista === 'semana' ? 'Semana del '.\Carbon\Carbon::parse($date)->startOfWeek()->format('d/m') : \Carbon\Carbon::parse($date)->translatedFormat('l, d \d\e F') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('agenda.index', ['date' => \Carbon\Carbon::parse($date)->{$vista === 'semana' ? 'subWeek' : 'subDay'}()->toDateString(), 'vista' => $vista, 'professional_id' => $filtroProfesional]) }}"
                   class="px-3 py-2 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">
                    ←
                </a>
                <a href="{{ route('agenda.index', ['vista' => $vista, 'professional_id' => $filtroProfesional]) }}"
                   class="px-4 py-2 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">
                    Hoy
                </a>
                <a href="{{ route('agenda.index', ['date' => \Carbon\Carbon::parse($date)->{$vista === 'semana' ? 'addWeek' : 'addDay'}()->toDateString(), 'vista' => $vista, 'professional_id' => $filtroProfesional]) }}"
                   class="px-3 py-2 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">
                    →
                </a>

                <div class="flex rounded-xl border border-warm-300 bg-white overflow-hidden">
                    <a href="{{ route('agenda.index', ['date' => $date, 'vista' => 'dia', 'professional_id' => $filtroProfesional]) }}"
                       class="px-4 py-2 text-sm font-semibold {{ $vista === 'dia' ? 'bg-turquoise-600 text-white' : 'text-petrol hover:bg-warm-100' }}">Día</a>
                    <a href="{{ route('agenda.index', ['date' => $date, 'vista' => 'semana', 'professional_id' => $filtroProfesional]) }}"
                       class="px-4 py-2 text-sm font-semibold {{ $vista === 'semana' ? 'bg-turquoise-600 text-white' : 'text-petrol hover:bg-warm-100' }}">Semana</a>
                </div>

                @if ($puedeGestionar && $profesionales->isNotEmpty())
                    <form method="GET" action="{{ route('agenda.index') }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="vista" value="{{ $vista }}">
                        <select name="professional_id" onchange="this.form.submit()" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                            <option value="">Todas las profesionales</option>
                            @foreach ($profesionales as $profesional)
                                <option value="{{ $profesional->id }}" @selected($filtroProfesional == $profesional->id)>{{ $profesional->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($vista === 'semana')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
                    @foreach ($dias as $dia)
                        @php
                            $fecha = $dia->toDateString();
                            $citasDia = $citasPorDia->get($fecha, collect());
                            $esHoy = $fecha === now('America/Bogota')->toDateString();
                        @endphp
                        <div class="bg-white rounded-2xl border {{ $esHoy ? 'border-turquoise-500 ring-1 ring-turquoise-500' : 'border-warm-200' }} shadow-card p-3 min-h-[10rem]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wide {{ $esHoy ? 'text-turquoise-700' : 'text-gray-500' }}">{{ $dia->translatedFormat('D') }}</div>
                                    <div class="text-lg font-extrabold text-petrol">{{ $dia->format('d/m') }}</div>
                                </div>
                                @if ($citasDia->isNotEmpty())
                                    <span class="text-xs font-bold text-turquoise-700 bg-turquoise-100 rounded-full px-2 py-0.5">{{ $citasDia->count() }}</span>
                                @endif
                            </div>
                            <div class="mt-2 space-y-1.5">
                                @forelse ($citasDia as $cita)
                                    <div class="rounded-xl bg-warm-100 border border-warm-200 px-2 py-1.5">
                                        <div class="text-xs font-bold text-petrol">{{ \App\Support\FormatoHora::hora($cita->starts_at) }} · {{ \Illuminate\Support\Str::limit($cita->customer_name, 14) }}</div>
                                        <x-badge :status="$cita->status" class="mt-1 scale-90 origin-left" />
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 text-center py-2">—</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    @if ($appointments->isEmpty())
                        <x-empty-state icon="calendar" title="Sin citas para este día" message="Cambia de día o crea una reserva desde el flujo de WhatsApp.">
                            <a href="{{ route('agenda.index', ['date' => $date, 'vista' => 'semana']) }}" class="text-sm font-semibold text-turquoise-700 hover:underline">Ver la semana completa →</a>
                        </x-empty-state>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-5">Hora</th>
                                        <th class="py-3 px-5">Cliente</th>
                                        <th class="py-3 px-5 hidden md:table-cell">Profesional</th>
                                        <th class="py-3 px-5 hidden md:table-cell">Servicio</th>
                                        <th class="py-3 px-5">Estado</th>
                                        <th class="py-3 px-5">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($appointments as $appointment)
                                        <tr class="hover:bg-warm-50">
                                            <td class="py-3 px-5 font-bold text-petrol">{{ \App\Support\FormatoHora::hora($appointment->starts_at) }}</td>
                                            <td class="py-3 px-5">{{ $appointment->customer_name }}</td>
                                            <td class="py-3 px-5 hidden md:table-cell">{{ $appointment->professional?->name }}</td>
                                            <td class="py-3 px-5 hidden md:table-cell">{{ $appointment->service?->name }}</td>
                                            <td class="py-3 px-5"><x-badge :status="$appointment->status" /></td>
                                            <td class="py-3 px-5">
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
                                                              onsubmit="return confirm('¿Cancelar esta reserva? El cliente recibirá un aviso.')">
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
            @endif

        </div>
    </div>
</x-app-layout>
