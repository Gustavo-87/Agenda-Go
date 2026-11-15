<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Clientes {{ $negocio ? '— '.$negocio->name : '' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Fichas, historial y consentimiento de datos.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($negocio === null)
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    @if (auth()->user()->isSuperAdmin())
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Selecciona un negocio</h3>
                            <p class="text-sm text-gray-500 mb-4">Elige el negocio cuyos clientes deseas consultar.</p>
                            <x-business-selector :negocios="$negocios" />
                        </div>
                    @else
                        <x-empty-state icon="users" title="No tienes un negocio asignado"
                            message="Cuando tu cuenta sea vinculada a un negocio, aquí verás sus clientes." />
                    @endif
                </div>
            @else
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="p-5">
                        <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap gap-2">
                            @if (auth()->user()->isSuperAdmin())
                                <input type="hidden" name="business_id" value="{{ $negocio->id }}">
                            @endif
                            <x-text-input name="q" :value="$q" placeholder="Buscar por nombre o teléfono…" class="flex-1 min-w-[12rem]" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($q !== '')
                                <a href="{{ route('clientes.index', array_filter(['business_id' => request()->query('business_id')])) }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">Limpiar</a>
                            @endif
                        </form>
                    </div>

                    @if ($clientes->isEmpty())
                        <x-empty-state icon="users" :title="$q !== '' ? 'Sin resultados' : 'Aún no hay clientes'" :message="$q !== '' ? 'No hay clientes que coincidan con la búsqueda.' : 'Los clientes se registran automáticamente al crear una reserva.'" />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-5">Nombre</th>
                                        <th class="py-3 px-5 hidden sm:table-cell">Teléfono</th>
                                        <th class="py-3 px-5 hidden md:table-cell">Ausencias</th>
                                        <th class="py-3 px-5 hidden lg:table-cell">Datos autorizados</th>
                                        <th class="py-3 px-5"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($clientes as $cliente)
                                        <tr class="hover:bg-warm-50">
                                            <td class="py-3 px-5 font-semibold text-petrol">
                                                {{ $cliente->name }}
                                                <div class="text-xs text-gray-500 font-normal sm:hidden">{{ $cliente->phone }}</div>
                                            </td>
                                            <td class="py-3 px-5 hidden sm:table-cell">{{ $cliente->phone }}</td>
                                            <td class="py-3 px-5 hidden md:table-cell">
                                                @if ($cliente->absences >= 2)
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">{{ $cliente->absences }} ausencias</span>
                                                @else
                                                    {{ $cliente->absences }}
                                                @endif
                                            </td>
                                            <td class="py-3 px-5 hidden lg:table-cell">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cliente->data_consent ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $cliente->data_consent ? 'Sí' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-5">
                                                <a href="{{ route('customers.show', $cliente) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-turquoise-700 hover:underline">
                                                    Ver ficha <x-icon name="arrow-right" class="w-4 h-4" />
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4">
                            {{ $clientes->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
