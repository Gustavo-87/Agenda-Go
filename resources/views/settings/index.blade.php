<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuración — {{ $negocio->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($puedeGestionar)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Política de reservas</h3>
                        <p class="text-sm text-gray-500 mb-4">Define la anticipación, la confirmación y el vencimiento de solicitudes.</p>

                        <form method="POST" action="{{ route('businesses.booking-policy', $negocio) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                    <input type="checkbox" name="booking_auto_confirm" value="1" class="rounded border-gray-300 text-teal-600" @checked($negocio->booking_auto_confirm)>
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

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Seguridad de la cuenta</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        La verificación en dos pasos protege el acceso. Los administradores siempre la usan al iniciar sesión.
                    </p>

                    <form method="POST" action="{{ route('profile.2fa') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="two_factor_enabled" value="{{ auth()->user()->two_factor_enabled ? 0 : 1 }}">
                        <x-primary-button>
                            {{ auth()->user()->two_factor_enabled ? 'Desactivar verificación en dos pasos' : 'Activar verificación en dos pasos' }}
                        </x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Datos del negocio</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <div><dt class="text-gray-500">Nombre</dt><dd class="font-medium text-gray-800">{{ $negocio->name }}</dd></div>
                        <div><dt class="text-gray-500">Identificador</dt><dd class="font-medium text-gray-800">{{ $negocio->slug }}</dd></div>
                        <div><dt class="text-gray-500">Sector</dt><dd class="font-medium text-gray-800">{{ $negocio->sector ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Estado</dt>
                            <dd>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $negocio->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $negocio->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
