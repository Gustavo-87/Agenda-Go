<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold">Negocios</h2>
                <p class="text-sm text-gray-500 mt-0.5">Alta, edición, activación y administradores de negocios.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1">Registrar negocio</h3>
                    <p class="text-sm text-gray-500 mb-4">Crea un nuevo negocio en la plataforma. Los campos con <span class="text-red-600 font-semibold">*</span> son obligatorios.</p>
                    <form method="POST" action="{{ route('admin.businesses.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @csrf
                        <div>
                            <x-input-label for="negocio-name" value="Nombre del negocio *" />
                            <x-text-input id="negocio-name" name="name" placeholder="Ej. Viglam" class="w-full mt-1" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="negocio-slug" value="Identificador (slug) *" />
                            <x-text-input id="negocio-slug" name="slug" placeholder="Ej. viglam" class="w-full mt-1" required />
                            <p class="text-xs text-gray-500 mt-1">Nombre único sin espacios ni mayúsculas.</p>
                            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="negocio-sector" value="Sector" />
                            <x-text-input id="negocio-sector" name="sector" placeholder="Ej. belleza" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-input-label for="negocio-phone" value="Teléfono de contacto" />
                            <x-text-input id="negocio-phone" name="phone" placeholder="Ej. 300 000 0000" class="w-full mt-1" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <x-primary-button><x-icon name="plus" class="w-4 h-4" />Registrar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-5">
                    <form method="GET" action="{{ route('admin.pantalla') }}" class="flex flex-wrap gap-2">
                        <x-text-input name="q" :value="$q" placeholder="Buscar por nombre o identificador…" class="flex-1 min-w-[12rem]" />
                        <x-primary-button>Buscar</x-primary-button>
                        @if ($q !== '')
                            <a href="{{ route('admin.pantalla') }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">Limpiar</a>
                        @endif
                    </form>
                </div>

                @if ($negocios->isEmpty())
                    <x-empty-state icon="briefcase" :title="$q !== '' ? 'Sin resultados' : 'Sin negocios registrados'" :message="$q !== '' ? 'Ningún negocio coincide con la búsqueda.' : 'Registra el primer negocio para comenzar.'" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-warm-100 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                    <th class="py-3 px-4">Negocio</th>
                                    <th class="py-3 px-4 hidden sm:table-cell">Estado</th>
                                    <th class="py-3 px-4 hidden md:table-cell">Membresía vigente</th>
                                    <th class="py-3 px-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-warm-100">
                                @foreach ($negocios as $item)
                                    @php $membresia = $item->memberships->sortByDesc('ends_on')->first(); @endphp
                                    <tr class="hover:bg-warm-50">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-petrol">{{ $item->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->slug }} · {{ $item->sector ?? '—' }}</div>
                                        </td>
                                        <td class="py-3 px-4 hidden sm:table-cell">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $item->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $item->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 hidden md:table-cell">
                                            @if ($membresia)
                                                {{ $membresia->ends_on }}
                                                <span class="text-xs text-gray-500">({{ $membresia->status }})</span>
                                            @else
                                                <span class="text-gray-500">Sin membresía</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.businesses.show', $item) }}" class="px-4 py-2.5 rounded-xl bg-petrol text-white text-xs font-semibold hover:bg-petrol-700 transition">
                                                    Gestionar
                                                </a>
                                                @unless ($item->active)
                                                    <form method="POST" action="{{ route('admin.businesses.activate', $item) }}">
                                                        @csrf
                                                        <x-primary-button type="submit">Activar</x-primary-button>
                                                    </form>
                                                @endunless
                                                @if ($item->active)
                                                    <form method="POST" action="{{ route('admin.businesses.suspend', $item) }}" onsubmit="return confirm('¿Suspender el negocio? Se bloquearán nuevas reservas.')">
                                                        @csrf
                                                        <x-danger-button type="submit">Suspender</x-danger-button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $negocios->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
