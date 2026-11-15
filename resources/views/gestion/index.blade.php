<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión — {{ $negocio->name }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="{ tab: 'profesionales' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Pestañas -->
            <div class="flex flex-wrap gap-2">
                @foreach (['profesionales' => 'Profesionales', 'servicios' => 'Servicios', 'horarios' => 'Horarios', 'excepciones' => 'Excepciones', 'festivos' => 'Festivos'] as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-teal-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 rounded-full text-sm font-medium border border-gray-200 shadow-sm">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Profesionales -->
            <div x-show="tab === 'profesionales'" class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Profesionales</h3>

                    <form method="POST" action="{{ route('professionals.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-6">
                        @csrf
                        <x-text-input name="name" placeholder="Nombre" class="w-full" required />
                        <x-text-input name="phone" placeholder="Teléfono (opcional)" class="w-full" />
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                        <div class="text-xs text-gray-500 self-center">Máximo 5 profesionales activos por negocio.</div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Nombre</th>
                                    <th class="py-2 pr-4">Teléfono</th>
                                    <th class="py-2 pr-4">Estado</th>
                                    <th class="py-2 pr-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($profesionales as $profesional)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $profesional->name }}</td>
                                        <td class="py-2 pr-4">{{ $profesional->phone }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $profesional->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $profesional->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <form method="POST" action="{{ route('professionals.update', $profesional) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-text-input name="name" :value="$profesional->name" class="w-32" required />
                                                <x-text-input name="phone" :value="$profesional->phone" class="w-28" />
                                                <x-secondary-button type="submit">Guardar</x-secondary-button>
                                            </form>
                                            <form method="POST" action="{{ route('professionals.toggle', $profesional) }}" class="mt-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-secondary-button type="submit">
                                                    {{ $profesional->active ? 'Desactivar' : 'Activar' }}
                                                </x-secondary-button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Servicios -->
            <div x-show="tab === 'servicios'" class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Servicios</h3>

                    <form method="POST" action="{{ route('services.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-6">
                        @csrf
                        <x-text-input name="name" placeholder="Servicio" class="w-full" required />
                        <select name="professional_id" required class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm text-sm">
                            @foreach ($profesionales as $profesional)
                                <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="duration_minutes" type="number" placeholder="Minutos" class="w-full" required />
                        <x-text-input name="price" type="number" placeholder="Precio" class="w-full" required />
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Servicio</th>
                                    <th class="py-2 pr-4">Profesional</th>
                                    <th class="py-2 pr-4">Duración</th>
                                    <th class="py-2 pr-4">Precio</th>
                                    <th class="py-2 pr-4">Estado</th>
                                    <th class="py-2 pr-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($servicios as $servicio)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $servicio->name }}</td>
                                        <td class="py-2 pr-4">{{ $profesionales->firstWhere('id', $servicio->professional_id)?->name }}</td>
                                        <td class="py-2 pr-4">{{ $servicio->duration_minutes }} min</td>
                                        <td class="py-2 pr-4">${{ number_format($servicio->price, 0, ',', '.') }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $servicio->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $servicio->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4 whitespace-nowrap">
                                            <form method="POST" action="{{ route('services.toggle', $servicio) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <x-secondary-button type="submit">
                                                    {{ $servicio->active ? 'Desactivar' : 'Activar' }}
                                                </x-secondary-button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Horarios -->
            <div x-show="tab === 'horarios'" class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Jornadas y descansos</h3>

                    <form method="POST" action="{{ route('schedules.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 mb-6">
                        @csrf
                        <select name="professional_id" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.action = '/professionals/' + this.value + '/schedules'">
                            @foreach ($profesionales as $profesional)
                                <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                            @endforeach
                        </select>
                        <select name="weekday" class="border-gray-300 rounded-md shadow-sm text-sm">
                            @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $indice => $dia)
                                <option value="{{ $indice }}">{{ $dia }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="start_time" type="time" class="w-full" required />
                        <x-text-input name="end_time" type="time" class="w-full" required />
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="hidden" name="is_break" value="0">
                            <input type="checkbox" name="is_break" value="1" class="rounded border-gray-300 text-teal-600">
                            Descanso
                        </label>
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                    </form>

                    @foreach ($profesionales as $profesional)
                        <div class="mb-4">
                            <h4 class="font-medium text-gray-700 mb-2">{{ $profesional->name }}</h4>
                            @php $lista = $horarios->get($profesional->id, collect()); @endphp
                            @if ($lista->isEmpty())
                                <p class="text-sm text-gray-500">Sin jornadas configuradas (se aplica el horario general 09:00–18:00).</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($lista->sortBy([['weekday', 'asc'], ['start_time', 'asc']]) as $horario)
                                        <form method="POST" action="{{ route('schedules.destroy', $horario) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 rounded-full text-xs font-medium border {{ $horario->is_break ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-teal-50 border-teal-200 text-teal-800' }} hover:opacity-80">
                                                {{ ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][$horario->weekday] }}
                                                {{ substr($horario->start_time, 0, 5) }}–{{ substr($horario->end_time, 0, 5) }}
                                                {{ $horario->is_break ? '· descanso' : '' }} ✕
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Excepciones -->
            <div x-show="tab === 'excepciones'" class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Vacaciones, bloqueos y horarios extra</h3>

                    <form method="POST" action="{{ route('exceptions.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 mb-6" id="form-excepcion">
                        @csrf
                        <select name="professional_id" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.action = '/professionals/' + this.value + '/exceptions'">
                            @foreach ($profesionales as $profesional)
                                <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                            @endforeach
                        </select>
                        <select name="kind" class="border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="vacation">Vacaciones</option>
                            <option value="block">Bloqueo</option>
                            <option value="extra">Horario extra</option>
                        </select>
                        <x-text-input name="starts_on" type="date" class="w-full" required />
                        <x-text-input name="ends_on" type="date" class="w-full" />
                        <x-text-input name="start_time" type="time" class="w-full" />
                        <x-text-input name="end_time" type="time" class="w-full" />
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                    </form>

                    @foreach ($profesionales as $profesional)
                        @php $lista = $excepciones->get($profesional->id, collect()); @endphp
                        @if ($lista->isNotEmpty())
                            <div class="mb-4">
                                <h4 class="font-medium text-gray-700 mb-2">{{ $profesional->name }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($lista as $excepcion)
                                        <form method="POST" action="{{ route('exceptions.destroy', $excepcion) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 border border-gray-200 text-gray-700 hover:opacity-80">
                                                {{ ['vacation' => 'Vacaciones', 'block' => 'Bloqueo', 'extra' => 'Extra'][$excepcion->kind] }}
                                                {{ $excepcion->starts_on }}{{ $excepcion->ends_on && $excepcion->ends_on !== $excepcion->starts_on ? ' → '.$excepcion->ends_on : '' }}
                                                ✕
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Festivos -->
            <div x-show="tab === 'festivos'" class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Festivos del negocio</h3>

                    <form method="POST" action="{{ route('holidays.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        @csrf
                        <x-text-input name="date" type="date" class="w-full" required />
                        <x-text-input name="name" placeholder="Nombre del festivo (opcional)" class="w-full" />
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                    </form>

                    @if ($festivos->isEmpty())
                        <p class="text-sm text-gray-500">Sin festivos configurados.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($festivos as $festivo)
                                <form method="POST" action="{{ route('holidays.destroy', $festivo) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 rounded-full text-xs font-medium bg-red-50 border border-red-200 text-red-800 hover:opacity-80">
                                        {{ $festivo->date }}{{ $festivo->name ? ' · '.$festivo->name : '' }} ✕
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
