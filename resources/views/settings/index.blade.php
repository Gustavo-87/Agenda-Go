<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Configuración {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Política de reservas y seguridad de la cuenta.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($negocio === null)
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
                        <h3 class="text-lg font-bold mb-4">Datos del negocio</h3>
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
