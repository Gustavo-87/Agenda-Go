<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold">Retención de datos</h2>
                <p class="text-sm text-gray-500 mt-0.5">Revisión de la conservación de 90 días tras la cancelación de membresías.</p>
            </div>
            <a href="{{ route('admin.pantalla') }}" class="px-4 py-2.5 rounded-xl border border-warm-300 bg-white text-sm font-semibold text-petrol hover:bg-warm-100">
                ← Volver a negocios
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1">Proceso seguro de purga</h3>
                    <p class="text-sm text-gray-500">
                        La purga nunca es automática: requiere revisión administrativa y autorización explícita por consola
                        (<code class="bg-warm-100 px-1 rounded">php artisan retention:purge {"{slug}"} --confirm={"{slug}"}</code>).
                        Conserva registros financieros, membresías, usuarios y auditoría por obligaciones legales.
                        Los negocios con <span class="font-semibold">retención legal</span> activa no pueden purgarse.
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Negocios con retención vencida</h3>

                    @if ($elegibles->isEmpty())
                        <x-empty-state icon="shield" title="Sin retenciones vencidas" message="Ningún negocio supera los 90 días posteriores a la cancelación." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-warm-100 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="py-3 px-4">Negocio</th>
                                        <th class="py-3 px-4">Fin del período</th>
                                        <th class="py-3 px-4">Datos operativos</th>
                                        <th class="py-3 px-4">Retención legal</th>
                                        <th class="py-3 px-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-warm-100">
                                    @foreach ($elegibles as $fila)
                                        <tr>
                                            <td class="py-3 px-4 font-semibold text-petrol">{{ $fila['negocio']->name }}</td>
                                            <td class="py-3 px-4">{{ $fila['fin_periodo'] }}</td>
                                            <td class="py-3 px-4">{{ $fila['clientes'] }} clientes · {{ $fila['citas'] }} citas · {{ $fila['mensajes'] }} mensajes</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $fila['negocio']->legal_hold ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800' }}">
                                                    {{ $fila['negocio']->legal_hold ? 'Activa (no purgable)' : 'Sin exclusión' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <form method="POST" action="{{ route('admin.businesses.legal-hold', $fila['negocio']) }}">
                                                    @csrf
                                                    <input type="hidden" name="legal_hold" value="{{ $fila['negocio']->legal_hold ? 0 : 1 }}">
                                                    <x-secondary-button type="submit">
                                                        {{ $fila['negocio']->legal_hold ? 'Quitar retención legal' : 'Marcar retención legal' }}
                                                    </x-secondary-button>
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

        </div>
    </div>
</x-app-layout>
