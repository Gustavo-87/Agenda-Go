<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Gestión {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Profesionales, servicios, horarios, excepciones y festivos.</p>
    </x-slot>

    <div class="py-6" x-data="{ tab: 'profesionales' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($negocio === null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    @if (auth()->user()->isSuperAdmin())
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Selecciona un negocio</h3>
                            <p class="text-sm text-gray-500 mb-4">Elige el negocio que deseas administrar.</p>
                            <x-business-selector :negocios="$negocios" />
                        </div>
                    @else
                        <x-empty-state icon="briefcase" title="No tienes un negocio asignado"
                            message="Cuando un administrador asigne tu cuenta a un negocio, aquí podrás gestionarlo." />
                    @endif
                </div>
            @else

                <!-- Pestañas -->
                <div class="flex flex-wrap gap-2">
                    @foreach (['profesionales' => 'Profesionales', 'servicios' => 'Servicios', 'horarios' => 'Horarios', 'excepciones' => 'Excepciones', 'festivos' => 'Festivos'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-petrol text-white' : 'bg-white text-petrol hover:bg-warm-100'"
                            class="px-4 py-2 rounded-xl text-sm font-semibold border border-warm-200 shadow-sm transition">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Profesionales -->
                <div x-show="tab === 'profesionales'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Profesionales</h3>

                        <form method="POST" action="{{ route('professionals.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-6">
                            @csrf
                            <x-text-input name="name" placeholder="Nombre" class="w-full" required />
                            <x-text-input name="phone" placeholder="Teléfono (opcional)" class="w-full" />
                            <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Agregar</x-primary-button>
                            <div class="text-xs text-gray-500 self-center">Máximo 5 profesionales activos por negocio.</div>
                        </form>

                        @if ($profesionales->isEmpty())
                            <x-empty-state icon="users" title="Sin profesionales" message="Agrega la primera profesional para comenzar a recibir reservas." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Nombre</th>
                                            <th class="py-3 px-4">Teléfono</th>
                                            <th class="py-3 px-4">Estado</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($profesionales as $profesional)
                                            <tr>
                                                <td class="py-3 px-4 font-semibold text-petrol">{{ $profesional->name }}</td>
                                                <td class="py-3 px-4">{{ $profesional->phone ?? '—' }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $profesional->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                        {{ $profesional->active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <form method="POST" action="{{ route('professionals.update', $profesional) }}" class="flex flex-wrap items-center gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <x-text-input name="name" :value="$profesional->name" class="w-32" required />
                                                            <x-text-input name="phone" :value="$profesional->phone" class="w-28" />
                                                            <x-secondary-button type="submit">Guardar</x-secondary-button>
                                                        </form>
                                                        <form method="POST" action="{{ route('professionals.toggle', $profesional) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <x-secondary-button type="submit">
                                                                {{ $profesional->active ? 'Desactivar' : 'Activar' }}
                                                            </x-secondary-button>
                                                        </form>
                                                        <form method="POST" action="{{ route('professionals.assign-user', $profesional) }}" class="flex items-center gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <select name="user_id" class="border-warm-300 rounded-xl text-xs focus:border-turquoise-500 focus:ring-turquoise-500">
                                                                <option value="">Sin usuario</option>
                                                                @foreach ($usuarios as $usuario)
                                                                    <option value="{{ $usuario->id }}" @selected($profesional->user_id == $usuario->id)>{{ $usuario->email }}</option>
                                                                @endforeach
                                                            </select>
                                                            <x-secondary-button type="submit">Vincular usuario</x-secondary-button>
                                                        </form>
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

                <!-- Servicios -->
                <div x-show="tab === 'servicios'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Servicios</h3>

                        <form method="POST" action="{{ route('services.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-6">
                            @csrf
                            <x-text-input name="name" placeholder="Servicio" class="w-full" required />
                            <select name="professional_id" required class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                @foreach ($profesionales as $profesional)
                                    <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                @endforeach
                            </select>
                            <x-text-input name="duration_minutes" type="number" placeholder="Minutos" class="w-full" required />
                            <x-text-input name="price" type="number" placeholder="Precio" class="w-full" required />
                            <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Agregar</x-primary-button>
                        </form>

                        @if ($servicios->isEmpty())
                            <x-empty-state icon="clipboard" title="Sin servicios" message="Crea el catálogo de servicios de tu negocio." />
                        @else
                            @foreach ($servicios as $servicio)
                                <form id="servicio-{{ $servicio->id }}" method="POST" action="{{ route('services.update', $servicio) }}">
                                    @csrf
                                    @method('PATCH')
                                </form>
                            @endforeach
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Servicio</th>
                                            <th class="py-3 px-4">Profesional</th>
                                            <th class="py-3 px-4">Duración</th>
                                            <th class="py-3 px-4">Precio</th>
                                            <th class="py-3 px-4">Estado</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($servicios as $servicio)
                                            <tr>
                                                <td class="py-3 px-4">
                                                    <x-text-input name="name" form="servicio-{{ $servicio->id }}" :value="$servicio->name" class="w-36" required />
                                                </td>
                                                <td class="py-3 px-4">
                                                    <select name="professional_id" form="servicio-{{ $servicio->id }}" class="border-warm-300 rounded-xl text-xs focus:border-turquoise-500 focus:ring-turquoise-500 w-32">
                                                        @foreach ($profesionales as $profesional)
                                                            <option value="{{ $profesional->id }}" @selected($servicio->professional_id == $profesional->id)>{{ $profesional->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <x-text-input name="duration_minutes" form="servicio-{{ $servicio->id }}" type="number" :value="$servicio->duration_minutes" class="w-20" required />
                                                </td>
                                                <td class="py-3 px-4">
                                                    <x-text-input name="price" form="servicio-{{ $servicio->id }}" type="number" :value="$servicio->price" class="w-24" required />
                                                </td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $servicio->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                        {{ $servicio->active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <x-secondary-button type="submit" form="servicio-{{ $servicio->id }}">Guardar</x-secondary-button>
                                                        <form method="POST" action="{{ route('services.toggle', $servicio) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <x-secondary-button type="submit">
                                                                {{ $servicio->active ? 'Desactivar' : 'Activar' }}
                                                            </x-secondary-button>
                                                        </form>
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

                <!-- Horarios -->
                <div x-show="tab === 'horarios'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Jornadas y descansos</h3>

                        <form method="POST" action="{{ route('schedules.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 mb-6">
                            @csrf
                            <select name="professional_id" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" onchange="this.form.action = '/professionals/' + this.value + '/schedules'">
                                @foreach ($profesionales as $profesional)
                                    <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                @endforeach
                            </select>
                            <select name="weekday" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $indice => $dia)
                                    <option value="{{ $indice }}">{{ $dia }}</option>
                                @endforeach
                            </select>
                            <x-text-input name="start_time" type="time" class="w-full" required />
                            <x-text-input name="end_time" type="time" class="w-full" required />
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="hidden" name="is_break" value="0">
                                <input type="checkbox" name="is_break" value="1" class="rounded border-warm-300 text-turquoise-600 focus:ring-turquoise-500">
                                Descanso
                            </label>
                            <x-primary-button class="justify-center">Agregar</x-primary-button>
                        </form>

                        @if ($profesionales->isEmpty())
                            <x-empty-state icon="clock" title="Sin profesionales" message="Agrega profesionales para configurar sus jornadas." />
                        @else
                            @foreach ($profesionales as $profesional)
                                <div class="mb-4">
                                    <h4 class="font-semibold text-petrol mb-2">{{ $profesional->name }}</h4>
                                    @php $lista = $horarios->get($profesional->id, collect()); @endphp
                                    @if ($lista->isEmpty())
                                        <p class="text-sm text-gray-500">Sin jornadas configuradas (se aplica el horario general 09:00–18:00).</p>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($lista->sortBy([['weekday', 'asc'], ['start_time', 'asc']]) as $horario)
                                                <form method="POST" action="{{ route('schedules.destroy', $horario) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold border {{ $horario->is_break ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-turquoise-100 border-turquoise-100 text-turquoise-700' }} hover:opacity-80">
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
                        @endif
                    </div>
                </div>

                <!-- Excepciones -->
                <div x-show="tab === 'excepciones'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Vacaciones, bloqueos y horarios extra</h3>

                        <form method="POST" action="{{ route('exceptions.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-7 gap-3 mb-6">
                            @csrf
                            <select name="professional_id" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" onchange="this.form.action = '/professionals/' + this.value + '/exceptions'">
                                @foreach ($profesionales as $profesional)
                                    <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                @endforeach
                            </select>
                            <select name="kind" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
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
                                    <h4 class="font-semibold text-petrol mb-2">{{ $profesional->name }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($lista as $excepcion)
                                            <form method="POST" action="{{ route('exceptions.destroy', $excepcion) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-warm-100 border border-warm-200 text-gray-700 hover:opacity-80">
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
                <div x-show="tab === 'festivos'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Festivos del negocio</h3>

                        <form method="POST" action="{{ route('holidays.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                            @csrf
                            <x-text-input name="date" type="date" class="w-full" required />
                            <x-text-input name="name" placeholder="Nombre del festivo (opcional)" class="w-full" />
                            <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Agregar</x-primary-button>
                        </form>

                        @if ($festivos->isEmpty())
                            <x-empty-state icon="calendar" title="Sin festivos configurados" message="Agrega los días festivos en los que el negocio no atenderá." />
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($festivos as $festivo)
                                    <form method="POST" action="{{ route('holidays.destroy', $festivo) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-50 border border-red-200 text-red-800 hover:opacity-80">
                                            {{ $festivo->date }}{{ $festivo->name ? ' · '.$festivo->name : '' }} ✕
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            @endif
        </div>
    </div>
</x-app-layout>
