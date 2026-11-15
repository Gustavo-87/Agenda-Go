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
                    <h3 class="text-lg font-bold mb-4">Registrar negocio</h3>
                    <form method="POST" action="{{ route('admin.businesses.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        @csrf
                        <x-text-input name="name" placeholder="Nombre" class="w-full" required />
                        <x-text-input name="slug" placeholder="Identificador (slug)" class="w-full" required />
                        <x-text-input name="sector" placeholder="Sector" class="w-full" />
                        <x-text-input name="phone" placeholder="Teléfono" class="w-full" />
                        <x-primary-button class="justify-center"><x-icon name="plus" class="w-4 h-4" />Registrar</x-primary-button>
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
