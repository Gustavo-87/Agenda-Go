<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Configuración {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Política de reservas y seguridad de la cuenta.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($negocio === null && ! auth()->user()->isSuperAdmin())
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    @if (auth()->user()->isSuperAdmin())
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Selecciona un negocio</h3>
                            <p class="text-sm text-gray-500 mb-4">Elige el negocio que deseas configurar.</p>
                            <x-business-selector :negocios="$negocios" />
                        </div>
                    @else
                        <x-empty-state icon="cog" title="No tienes un negocio asignado"
                            message="La configuración del negocio estará disponible cuando tu cuenta sea vinculada. Mientras tanto, gestiona la seguridad de tu cuenta abajo." />
                    @endif
                </div>
            @endif

            @if ($miProfesional ?? null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="users" /></div>
                            <div>
                                <h3 class="text-lg font-bold">Mis datos de contacto</h3>
                                <p class="text-sm text-gray-500">Así te verán los clientes en la agenda.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('professionals.contact', $miProfesional) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label value="Nombre" />
                                <x-text-input name="name" :value="$miProfesional->name" class="w-full mt-1" required />
                            </div>
                            <div>
                                <x-input-label value="Teléfono" />
                                <x-text-input name="phone" :value="$miProfesional->phone" class="w-full mt-1" />
                            </div>
                            <div class="flex items-end">
                                <x-primary-button>Guardar</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($miProfesional ?? null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="clock" /></div>
                            <div>
                                <h3 class="text-lg font-bold">Mi disponibilidad</h3>
                                <p class="text-sm text-gray-500">Jornadas, descansos y bloqueos de mi agenda (RN-015).</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('schedules.store', $miProfesional) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 mt-4">
                            @csrf
                            <select name="weekday" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $indice => $dia)
                                    <option value="{{ $indice }}">{{ $dia }}</option>
                                @endforeach
                            </select>
                            <x-text-input name="start_time" type="time" class="w-full" aria-label="Hora de inicio" required />
                            <x-text-input name="end_time" type="time" class="w-full" aria-label="Hora de fin" required />
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="hidden" name="is_break" value="0">
                                <input type="checkbox" name="is_break" value="1" class="rounded border-warm-300 text-turquoise-600 focus:ring-turquoise-500">
                                Descanso
                            </label>
                            <x-primary-button class="justify-center">Agregar jornada</x-primary-button>
                        </form>

                        <div class="flex flex-wrap gap-2 mt-4">
                            @forelse ($misHorarios as $horario)
                                <form method="POST" action="{{ route('schedules.destroy', $horario) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold border {{ $horario->is_break ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-turquoise-100 border-turquoise-100 text-turquoise-700' }} hover:opacity-80">
                                        {{ ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][$horario->weekday] }}
                                        {{ \App\Support\FormatoHora::hora($horario->start_time) }}–{{ \App\Support\FormatoHora::hora($horario->end_time) }}
                                        {{ $horario->is_break ? '· descanso' : '' }} ✕
                                    </button>
                                </form>
                            @empty
                                <p class="text-sm text-gray-500">Sin jornadas configuradas (se aplica el horario general 09:00–18:00).</p>
                            @endforelse
                        </div>

                        <h4 class="text-sm font-bold text-petrol mt-6 mb-2">Vacaciones, bloqueos y horarios extra</h4>
                        <form method="POST" action="{{ route('exceptions.store', $miProfesional) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                            @csrf
                            <select name="kind" class="border-warm-300 rounded-xl text-sm focus:border-turquoise-500 focus:ring-turquoise-500">
                                <option value="vacation">Vacaciones</option>
                                <option value="block">Bloqueo</option>
                                <option value="extra">Horario extra</option>
                            </select>
                            <x-text-input name="starts_on" type="date" class="w-full" aria-label="Desde" required />
                            <x-text-input name="ends_on" type="date" class="w-full" aria-label="Hasta" />
                            <x-text-input name="start_time" type="time" class="w-full" aria-label="Hora de inicio" />
                            <x-text-input name="end_time" type="time" class="w-full" aria-label="Hora de fin" />
                            <x-primary-button class="justify-center">Registrar</x-primary-button>
                        </form>

                        <div class="flex flex-wrap gap-2 mt-4">
                            @forelse ($misExcepciones as $excepcion)
                                <form method="POST" action="{{ route('exceptions.destroy', $excepcion) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-warm-100 border border-warm-200 text-gray-700 hover:opacity-80">
                                        {{ ['vacation' => 'Vacaciones', 'block' => 'Bloqueo', 'extra' => 'Extra'][$excepcion->kind] }}
                                        {{ $excepcion->starts_on }}{{ $excepcion->ends_on && $excepcion->ends_on !== $excepcion->starts_on ? ' → '.$excepcion->ends_on : '' }}
                                        ✕
                                    </button>
                                </form>
                            @empty
                                <p class="text-sm text-gray-500">Sin excepciones registradas.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            @if ($puedeGestionar)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="cog" /></div>
                            <div>
                                <h3 class="text-lg font-bold">Política de reservas</h3>
                                <p class="text-sm text-gray-500">Anticipación, confirmación y vencimiento de solicitudes.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('businesses.booking-policy', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="booking_min_notice_minutes" value="Anticipación mínima (minutos)" />
                                <x-text-input id="booking_min_notice_minutes" name="booking_min_notice_minutes" type="number" :value="$negocio->booking_min_notice_minutes" class="w-full mt-1" />
                            </div>
                            <div>
                                <x-input-label for="booking_max_advance_days" value="Anticipación máxima (días)" />
                                <x-text-input id="booking_max_advance_days" name="booking_max_advance_days" type="number" :value="$negocio->booking_max_advance_days" class="w-full mt-1" />
                            </div>
                            <div>
                                <x-input-label for="booking_pending_ttl_minutes" value="Vencimiento de solicitudes (minutos)" />
                                <x-text-input id="booking_pending_ttl_minutes" name="booking_pending_ttl_minutes" type="number" :value="$negocio->booking_pending_ttl_minutes" class="w-full mt-1" />
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="hidden" name="booking_auto_confirm" value="0">
                                    <input type="checkbox" name="booking_auto_confirm" value="1" class="rounded border-warm-300 text-turquoise-600 focus:ring-turquoise-500" @checked($negocio->booking_auto_confirm)>
                                    Confirmación automática
                                </label>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-4">
                                <x-primary-button>Guardar política</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="shield" /></div>
                        <div>
                            <h3 class="text-lg font-bold">Seguridad de la cuenta</h3>
                            <p class="text-sm text-gray-500">La verificación en dos pasos protege el acceso. Los administradores siempre la usan al iniciar sesión.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        <form method="POST" action="{{ route('profile.2fa') }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="two_factor_enabled" value="{{ auth()->user()->two_factor_enabled ? 0 : 1 }}">
                            <x-primary-button>
                                {{ auth()->user()->two_factor_enabled ? 'Desactivar verificación en dos pasos' : 'Activar verificación en dos pasos' }}
                            </x-primary-button>
                        </form>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ auth()->user()->two_factor_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                            {{ auth()->user()->two_factor_enabled ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>
            </div>

            @if ($negocio !== null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="bell" /></div>
                            <div>
                                <h3 class="text-lg font-bold">Consumo de mensajería</h3>
                                <p class="text-sm text-gray-500">Mensajes incluidos en la membresía, utilizados, disponibles y excedentes autorizados por la plataforma.</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4 text-sm">
                            <div class="rounded-xl bg-warm-100 p-4">
                                <dt class="text-gray-500">Incluido</dt>
                                <dd class="text-2xl font-extrabold text-petrol mt-1">{{ $negocio->messaging_included ?? 'Por definir' }}</dd>
                            </div>
                            <div class="rounded-xl bg-warm-100 p-4">
                                <dt class="text-gray-500">Utilizado</dt>
                                <dd class="text-2xl font-extrabold text-petrol mt-1">{{ $consumoMensajeria }}</dd>
                            </div>
                            <div class="rounded-xl bg-warm-100 p-4">
                                <dt class="text-gray-500">Disponible</dt>
                                <dd class="text-2xl font-extrabold text-petrol mt-1">
                                    {{ $negocio->messaging_included !== null ? max($negocio->messaging_included - $consumoMensajeria, 0) : '—' }}
                                </dd>
                            </div>
                            <div class="rounded-xl bg-warm-100 p-4">
                                <dt class="text-gray-500">Excedentes autorizados</dt>
                                <dd class="text-2xl font-extrabold text-petrol mt-1">{{ $negocio->messaging_overage_authorized ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            @if ($negocio !== null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-1">Datos del negocio</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="font-semibold text-petrol">{{ $negocio->name }}</dd></div>
                            <div><dt class="text-gray-500">Identificador</dt><dd class="font-semibold text-petrol">{{ $negocio->slug }}</dd></div>
                            <div><dt class="text-gray-500">Sector</dt><dd class="font-semibold text-petrol">{{ $negocio->sector ?? '—' }}</dd></div>
                            <div><dt class="text-gray-500">Estado</dt>
                                <dd>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $negocio->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $negocio->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
