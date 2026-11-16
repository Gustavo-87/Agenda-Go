<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Gestión {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Profesionales, servicios, horarios, excepciones y festivos.</p>
    </x-slot>

    <div class="py-6" x-data="{
            tab: {!! \Illuminate\Support\Js::from(request()->query('tab', 'profesionales')) !!},
            profesional: {!! \Illuminate\Support\Js::from((string) old('professional_id', (string) request()->query('profesional', ''))) !!},
            dia: {!! \Illuminate\Support\Js::from((string) old('weekday', (string) request()->query('dia', ''))) !!},
            nuevo: null,
            sync() {
                const params = new URLSearchParams();
                params.set('tab', this.tab);
                if (this.profesional) { params.set('profesional', this.profesional); }
                if (this.dia !== '') { params.set('dia', this.dia); }
                history.replaceState(null, '', '/gestion?' + params.toString());
            }
        }">
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
                        <button type="button" @click="tab = '{{ $key }}'; nuevo = null; sync()"
                            :class="tab === '{{ $key }}' ? 'bg-petrol text-white' : 'bg-white text-petrol hover:bg-warm-100'"
                            class="px-4 py-2 rounded-xl text-sm font-semibold border border-warm-200 shadow-sm transition">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- ===================== Profesionales ===================== -->
                <div x-show="tab === 'profesionales'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-bold">Profesionales</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('gestion.index') }}" class="flex gap-2">
                                    <input type="hidden" name="tab" value="profesionales">
                                    <x-text-input name="q" :value="$q" placeholder="Buscar por nombre o teléfono…" class="w-52" />
                                    <x-secondary-button>Buscar</x-secondary-button>
                                </form>
                                <x-primary-button type="button" @click="nuevo = nuevo === 'profesionales' ? null : 'profesionales'">
                                    <x-icon name="plus" class="w-4 h-4" />Nuevo profesional
                                </x-primary-button>
                            </div>
                        </div>

                        <!-- Panel de creación -->
                        <div x-show="nuevo === 'profesionales'" class="rounded-2xl border border-warm-200 bg-warm-100/40 p-4 mb-6">
                            <h4 class="text-sm font-bold text-petrol mb-3">Agregar profesional</h4>
                            <form method="POST" action="{{ route('professionals.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @csrf
                                <div>
                                    <x-input-label value="Nombre *" />
                                    <x-text-input name="name" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Teléfono" />
                                    <x-text-input name="phone" class="w-full mt-1" />
                                </div>
                                <div class="flex items-end">
                                    <x-primary-button class="justify-center w-full">Agregar</x-primary-button>
                                </div>
                            </form>
                            <p class="text-xs text-gray-500 mt-2">Máximo 5 profesionales activos por negocio.</p>
                        </div>

                        <h4 class="text-sm font-bold text-petrol mb-2">Listado de profesionales</h4>
                        @if ($profesionales->isEmpty())
                            <x-empty-state icon="users" title="Sin profesionales" message="Agrega la primera profesional para comenzar a recibir reservas." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Nombre</th>
                                            <th class="py-3 px-4 hidden sm:table-cell">Teléfono</th>
                                            <th class="py-3 px-4">Estado</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($profesionales as $profesional)
                                            <tr class="hover:bg-warm-50">
                                                <td class="py-3 px-4 font-semibold text-petrol">
                                                    {{ $profesional->name }}
                                                    <div class="text-xs text-gray-500 font-normal sm:hidden">{{ $profesional->phone }}</div>
                                                </td>
                                                <td class="py-3 px-4 hidden sm:table-cell">{{ $profesional->phone ?? '—' }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $profesional->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                        {{ $profesional->active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <x-secondary-button type="button" x-on:click.prevent="$dispatch('open', { name: 'editar-profesional-{{ $profesional->id }}' })">
                                                            Editar
                                                        </x-secondary-button>
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
                                                            <select name="user_id" class="border-warm-300 rounded-xl text-xs focus:border-turquoise-500 focus:ring-turquoise-500" aria-label="Usuario vinculado">
                                                                <option value="">Sin usuario</option>
                                                                @foreach ($usuarios as $usuario)
                                                                    <option value="{{ $usuario->id }}" @selected($profesional->user_id == $usuario->id)>{{ $usuario->email }}</option>
                                                                @endforeach
                                                            </select>
                                                            <x-secondary-button type="submit">Vincular</x-secondary-button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Modal de edición -->
                                            <x-modal name="editar-profesional-{{ $profesional->id }}">
                                                <form method="POST" action="{{ route('professionals.update', $profesional) }}" class="p-6 space-y-4">
                                                    @csrf
                                                    @method('PATCH')
                                                    <h3 class="text-lg font-bold text-petrol">Editar profesional</h3>
                                                    <div>
                                                        <x-input-label value="Nombre *" />
                                                        <x-text-input name="name" :value="$profesional->name" class="w-full mt-1" required />
                                                    </div>
                                                    <div>
                                                        <x-input-label value="Teléfono" />
                                                        <x-text-input name="phone" :value="$profesional->phone" class="w-full mt-1" />
                                                    </div>
                                                    <div class="flex justify-end gap-3">
                                                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                                                        <x-primary-button>Guardar</x-primary-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ===================== Servicios ===================== -->
                <div x-show="tab === 'servicios'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-bold">Servicios</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('gestion.index') }}" class="flex flex-wrap gap-2">
                                    <input type="hidden" name="tab" value="servicios">
                                    <x-text-input name="q" :value="$q" placeholder="Buscar servicio…" class="w-40" />
                                    <select name="estado" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" aria-label="Filtrar por estado">
                                        <option value="todos">Todos los estados</option>
                                        <option value="active" @selected($estado === 'active')>Activos</option>
                                        <option value="inactive" @selected($estado === 'inactive')>Inactivos</option>
                                    </select>
                                    <x-secondary-button>Buscar</x-secondary-button>
                                </form>
                                <x-primary-button type="button" @click="nuevo = nuevo === 'servicios' ? null : 'servicios'">
                                    <x-icon name="plus" class="w-4 h-4" />Nuevo servicio
                                </x-primary-button>
                            </div>
                        </div>

                        <div x-show="nuevo === 'servicios'" class="rounded-2xl border border-warm-200 bg-warm-100/40 p-4 mb-6">
                            <h4 class="text-sm font-bold text-petrol mb-3">Agregar servicio</h4>
                            <form method="POST" action="{{ route('services.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @csrf
                                <div>
                                    <x-input-label value="Servicio *" />
                                    <x-text-input name="name" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Profesional *" />
                                    <select name="professional_id" required class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                        @foreach ($profesionales as $profesional)
                                            <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Duración (minutos) *" />
                                    <x-text-input name="duration_minutes" type="number" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Precio (COP) *" />
                                    <x-text-input name="price" type="number" class="w-full mt-1" required />
                                </div>
                                <div class="flex items-end">
                                    <x-primary-button class="justify-center w-full">Agregar</x-primary-button>
                                </div>
                            </form>
                        </div>

                        <h4 class="text-sm font-bold text-petrol mb-2">Listado de servicios</h4>
                        @if ($servicios->isEmpty())
                            <x-empty-state icon="clipboard" title="Sin servicios" message="Crea el catálogo de servicios de tu negocio." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Servicio</th>
                                            <th class="py-3 px-4 hidden sm:table-cell">Profesional</th>
                                            <th class="py-3 px-4 hidden md:table-cell">Duración</th>
                                            <th class="py-3 px-4 hidden md:table-cell">Precio</th>
                                            <th class="py-3 px-4">Estado</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($servicios as $servicio)
                                            <tr class="hover:bg-warm-50">
                                                <td class="py-3 px-4 font-semibold text-petrol">
                                                    {{ $servicio->name }}
                                                    <div class="text-xs text-gray-500 font-normal sm:hidden">
                                                        {{ $profesionales->firstWhere('id', $servicio->professional_id)?->name }} · {{ $servicio->duration_minutes }} min · ${{ number_format($servicio->price, 0, ',', '.') }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 hidden sm:table-cell">{{ $profesionales->firstWhere('id', $servicio->professional_id)?->name }}</td>
                                                <td class="py-3 px-4 hidden md:table-cell">{{ $servicio->duration_minutes }} min</td>
                                                <td class="py-3 px-4 hidden md:table-cell">${{ number_format($servicio->price, 0, ',', '.') }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $servicio->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                        {{ $servicio->active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <x-secondary-button type="button" x-on:click.prevent="$dispatch('open', { name: 'editar-servicio-{{ $servicio->id }}' })">
                                                            Editar
                                                        </x-secondary-button>
                                                        <form method="POST" action="{{ route('services.toggle', $servicio) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <x-secondary-button type="submit">
                                                                {{ $servicio->active ? 'Desactivar' : 'Activar' }}
                                                            </x-secondary-button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            <x-modal name="editar-servicio-{{ $servicio->id }}">
                                                <form method="POST" action="{{ route('services.update', $servicio) }}" class="p-6 space-y-4">
                                                    @csrf
                                                    @method('PATCH')
                                                    <h3 class="text-lg font-bold text-petrol">Editar servicio</h3>
                                                    <div>
                                                        <x-input-label value="Servicio *" />
                                                        <x-text-input name="name" :value="$servicio->name" class="w-full mt-1" required />
                                                    </div>
                                                    <div>
                                                        <x-input-label value="Profesional *" />
                                                        <select name="professional_id" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                                            @foreach ($profesionales as $profesional)
                                                                <option value="{{ $profesional->id }}" @selected($servicio->professional_id == $profesional->id)>{{ $profesional->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <x-input-label value="Duración (minutos) *" />
                                                        <x-text-input name="duration_minutes" type="number" :value="$servicio->duration_minutes" class="w-full mt-1" required />
                                                    </div>
                                                    <div>
                                                        <x-input-label value="Precio (COP) *" />
                                                        <x-text-input name="price" type="number" :value="$servicio->price" class="w-full mt-1" required />
                                                    </div>
                                                    <div class="flex justify-end gap-3">
                                                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                                                        <x-primary-button>Guardar</x-primary-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ===================== Horarios ===================== -->
                <div x-show="tab === 'horarios'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-bold">Horarios</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('gestion.index') }}" class="flex gap-2">
                                    <input type="hidden" name="tab" value="horarios">
                                    <select name="professional_id" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" aria-label="Filtrar por profesional" onchange="this.form.submit()">
                                        <option value="">Todas las profesionales</option>
                                        @foreach ($profesionales as $profesional)
                                            <option value="{{ $profesional->id }}" @selected($profesionalFiltro == $profesional->id)>{{ $profesional->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <x-primary-button type="button" @click="nuevo = nuevo === 'horarios' ? null : 'horarios'">
                                    <x-icon name="plus" class="w-4 h-4" />Nueva jornada
                                </x-primary-button>
                            </div>
                        </div>

                        <div x-show="nuevo === 'horarios'" class="rounded-2xl border border-warm-200 bg-warm-100/40 p-4 mb-6">
                            <h4 class="text-sm font-bold text-petrol mb-3">Agregar jornada</h4>
                            <form method="POST" action="{{ route('schedules.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @csrf
                                <div>
                                    <x-input-label value="Profesional *" />
                                    <select name="professional_id" x-model="profesional" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500"
                                        @change="sync(); this.form.action = '/professionals/' + this.value + '/schedules'">
                                        @foreach ($profesionales as $profesional)
                                            <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Día de la semana" />
                                    <select name="weekday" x-model="dia" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500" @change="sync()">
                                        @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $indice => $dia)
                                            <option value="{{ $indice }}">{{ $dia }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Hora de inicio" />
                                    <x-text-input name="start_time" type="time" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Hora de finalización" />
                                    <x-text-input name="end_time" type="time" class="w-full mt-1" required />
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 text-sm text-gray-600">
                                        <input type="hidden" name="is_break" value="0">
                                        <input type="checkbox" name="is_break" value="1" class="rounded border-warm-300 text-turquoise-600 focus:ring-turquoise-500">
                                        Descanso
                                    </label>
                                </div>
                                <div class="flex items-end">
                                    <x-primary-button class="justify-center w-full">Agregar</x-primary-button>
                                </div>
                            </form>
                        </div>

                        <h4 class="text-sm font-bold text-petrol mb-2">Listado de jornadas y descansos</h4>
                        @php
                            $filasHorarios = collect();
                            foreach ($horarios as $pid => $lista) {
                                foreach ($lista as $horario) {
                                    $filasHorarios->push($horario);
                                }
                            }
                            $filasHorarios = $filasHorarios->sortBy([['weekday', 'asc'], ['start_time', 'asc']]);
                        @endphp
                        @if ($filasHorarios->isEmpty())
                            <x-empty-state icon="clock" title="Sin jornadas configuradas" message="Las profesionales atenderán en el horario general 09:00–18:00." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Profesional</th>
                                            <th class="py-3 px-4">Día</th>
                                            <th class="py-3 px-4">Horario</th>
                                            <th class="py-3 px-4">Tipo</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($filasHorarios as $horario)
                                            <tr class="hover:bg-warm-50">
                                                <td class="py-3 px-4 font-semibold text-petrol">{{ $profesionales->firstWhere('id', $horario->professional_id)?->name }}</td>
                                                <td class="py-3 px-4">{{ ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'][$horario->weekday] }}</td>
                                                <td class="py-3 px-4">{{ \App\Support\FormatoHora::hora($horario->start_time) }}–{{ \App\Support\FormatoHora::hora($horario->end_time) }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $horario->is_break ? 'bg-amber-100 text-amber-800' : 'bg-turquoise-100 text-turquoise-700' }}">
                                                        {{ $horario->is_break ? 'Descanso' : 'Jornada' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <form method="POST" action="{{ route('schedules.destroy', $horario) }}" onsubmit="return confirm('¿Eliminar este horario?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit">Eliminar</x-danger-button>
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

                <!-- ===================== Excepciones ===================== -->
                <div x-show="tab === 'excepciones'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-bold">Excepciones</h3>
                            <x-primary-button type="button" @click="nuevo = nuevo === 'excepciones' ? null : 'excepciones'">
                                <x-icon name="plus" class="w-4 h-4" />Nueva excepción
                            </x-primary-button>
                        </div>

                        <div x-show="nuevo === 'excepciones'" class="rounded-2xl border border-warm-200 bg-warm-100/40 p-4 mb-6">
                            <h4 class="text-sm font-bold text-petrol mb-3">Registrar excepción</h4>
                            <form method="POST" action="{{ route('exceptions.store', $profesionales->first()?->id ?? 0) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @csrf
                                <div>
                                    <x-input-label value="Profesional" />
                                    <select name="professional_id" x-model="profesional" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500"
                                        @change="sync(); this.form.action = '/professionals/' + this.value + '/exceptions'">
                                        @foreach ($profesionales as $profesional)
                                            <option value="{{ $profesional->id }}">{{ $profesional->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Tipo" />
                                    <select name="kind" class="w-full mt-1 border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                        <option value="vacation">Vacaciones</option>
                                        <option value="block">Bloqueo</option>
                                        <option value="extra">Horario extra</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Desde (fecha) *" />
                                    <x-text-input name="starts_on" type="date" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Hasta (fecha)" />
                                    <x-text-input name="ends_on" type="date" class="w-full mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Hora de inicio" />
                                    <x-text-input name="start_time" type="time" class="w-full mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Hora de finalización" />
                                    <x-text-input name="end_time" type="time" class="w-full mt-1" />
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <x-primary-button class="justify-center w-full sm:w-auto">Registrar</x-primary-button>
                                </div>
                            </form>
                        </div>

                        <h4 class="text-sm font-bold text-petrol mb-2">Listado de excepciones</h4>
                        @if ($excepciones->isEmpty())
                            <x-empty-state icon="calendar" title="Sin excepciones" message="Vacaciones, bloqueos y horarios extra aparecerán aquí." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Profesional</th>
                                            <th class="py-3 px-4">Tipo</th>
                                            <th class="py-3 px-4 hidden sm:table-cell">Desde</th>
                                            <th class="py-3 px-4 hidden sm:table-cell">Hasta</th>
                                            <th class="py-3 px-4 hidden md:table-cell">Horario extra</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($excepciones as $excepcion)
                                            <tr class="hover:bg-warm-50">
                                                <td class="py-3 px-4 font-semibold text-petrol">{{ $profesionales->firstWhere('id', $excepcion->professional_id)?->name }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-warm-100 text-gray-700">
                                                        {{ ['vacation' => 'Vacaciones', 'block' => 'Bloqueo', 'extra' => 'Horario extra'][$excepcion->kind] ?? $excepcion->kind }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 hidden sm:table-cell">{{ $excepcion->starts_on }}</td>
                                                <td class="py-3 px-4 hidden sm:table-cell">{{ $excepcion->ends_on ?? '—' }}</td>
                                                <td class="py-3 px-4 hidden md:table-cell">
                                                    {{ $excepcion->start_time ? \App\Support\FormatoHora::hora($excepcion->start_time).'–'.\App\Support\FormatoHora::hora($excepcion->end_time) : '—' }}
                                                </td>
                                                <td class="py-3 px-4">
                                                    <form method="POST" action="{{ route('exceptions.destroy', $excepcion) }}" onsubmit="return confirm('¿Eliminar esta excepción?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit">Eliminar</x-danger-button>
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

                <!-- ===================== Festivos ===================== -->
                <div x-show="tab === 'festivos'" class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-bold">Festivos</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('gestion.index') }}" class="flex gap-2">
                                    <input type="hidden" name="tab" value="festivos">
                                    <x-text-input name="q" :value="$q" placeholder="Buscar por fecha o nombre…" class="w-52" />
                                    <x-secondary-button>Buscar</x-secondary-button>
                                </form>
                                <x-primary-button type="button" @click="nuevo = nuevo === 'festivos' ? null : 'festivos'">
                                    <x-icon name="plus" class="w-4 h-4" />Nuevo festivo
                                </x-primary-button>
                            </div>
                        </div>

                        <div x-show="nuevo === 'festivos'" class="rounded-2xl border border-warm-200 bg-warm-100/40 p-4 mb-6">
                            <h4 class="text-sm font-bold text-petrol mb-3">Agregar festivo</h4>
                            <form method="POST" action="{{ route('holidays.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @csrf
                                <div>
                                    <x-input-label value="Fecha *" />
                                    <x-text-input name="date" type="date" class="w-full mt-1" required />
                                </div>
                                <div>
                                    <x-input-label value="Nombre del festivo" />
                                    <x-text-input name="name" class="w-full mt-1" />
                                </div>
                                <div class="flex items-end">
                                    <x-primary-button class="justify-center w-full">Agregar</x-primary-button>
                                </div>
                            </form>
                        </div>

                        <h4 class="text-sm font-bold text-petrol mb-2">Listado de festivos</h4>
                        @if ($festivos->isEmpty())
                            <x-empty-state icon="calendar" title="Sin festivos configurados" message="Agrega los días festivos en los que el negocio no atenderá." />
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-warm-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="py-3 px-4">Fecha</th>
                                            <th class="py-3 px-4">Nombre</th>
                                            <th class="py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-warm-100">
                                        @foreach ($festivos as $festivo)
                                            <tr class="hover:bg-warm-50">
                                                <td class="py-3 px-4 font-semibold text-petrol">{{ $festivo->date }}</td>
                                                <td class="py-3 px-4">{{ $festivo->name ?? '—' }}</td>
                                                <td class="py-3 px-4">
                                                    <form method="POST" action="{{ route('holidays.destroy', $festivo) }}" onsubmit="return confirm('¿Eliminar este festivo?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit">Eliminar</x-danger-button>
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

                <!-- Invitaciones de profesionales -->
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-1">Invitar profesionales</h3>
                        <p class="text-sm text-gray-500 mb-4">Recibirán un correo con un enlace temporal para crear su contraseña.</p>

                        <form method="POST" action="{{ route('invitations.store', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @csrf
                            <input type="hidden" name="role" value="professional">
                            <div>
                                <x-input-label value="Correo electrónico *" />
                                <x-text-input name="email" type="email" class="w-full mt-1" aria-label="Correo electrónico de la profesional a invitar" required />
                            </div>
                            <div class="flex items-end">
                                <x-primary-button class="justify-center w-full">Enviar invitación</x-primary-button>
                            </div>
                        </form>

                        @if ($invitaciones->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2 mt-4">
                                <span class="text-xs text-gray-500">Pendientes:</span>
                                @foreach ($invitaciones as $invitacion)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-semibold bg-amber-50 border border-amber-200 text-amber-800">
                                        {{ $invitacion->email }}
                                        <form method="POST" action="{{ route('invitations.resend', $invitacion) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="underline hover:opacity-70" title="Reenviar">↻</button>
                                        </form>
                                        <form method="POST" action="{{ route('invitations.revoke', $invitacion) }}" class="inline" onsubmit="return confirm('¿Revocar esta invitación?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="underline hover:opacity-70" title="Revocar">✕</button>
                                        </form>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            @endif
        </div>
    </div>
</x-app-layout>
