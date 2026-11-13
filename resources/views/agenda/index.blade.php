<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Mi agenda</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <form method="GET" action="{{ route('agenda.index') }}" class="mb-6">
            <label for="date">Seleccionar fecha:</label>
            <input type="date" id="date" name="date"
                   value="{{ $date }}" class="rounded border-gray-300">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Consultar
            </button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">Hora</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3">Servicio</th>
                        <th class="p-3">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr class="border-t">
                            <td class="p-3">
                                {{ substr($appointment->starts_at, 11, 5) }}
                            </td>
                            <td class="p-3">{{ $appointment->customer_name }}</td>
                            <td class="p-3">{{ $appointment->service_name }}</td>
                            <td class="p-3">{{ $appointment->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500">
                                No hay citas para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
