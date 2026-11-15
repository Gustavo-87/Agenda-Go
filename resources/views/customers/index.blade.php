<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Clientes — {{ $negocio->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($clientes->isEmpty())
                        <p class="text-sm text-gray-500">Aún no hay clientes registrados. Se crean automáticamente al reservar.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Nombre</th>
                                        <th class="py-2 pr-4">Teléfono</th>
                                        <th class="py-2 pr-4">Ausencias</th>
                                        <th class="py-2 pr-4">Datos autorizados</th>
                                        <th class="py-2 pr-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($clientes as $cliente)
                                        <tr>
                                            <td class="py-2 pr-4 font-medium text-gray-800">{{ $cliente->name }}</td>
                                            <td class="py-2 pr-4">{{ $cliente->phone }}</td>
                                            <td class="py-2 pr-4">
                                                @if ($cliente->absences >= 2)
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $cliente->absences }}</span>
                                                @else
                                                    {{ $cliente->absences }}
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cliente->data_consent ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $cliente->data_consent ? 'Sí' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="py-2 pr-4">
                                                <a href="{{ route('customers.show', $cliente) }}" class="text-teal-700 font-medium hover:underline">
                                                    Ver ficha →
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
