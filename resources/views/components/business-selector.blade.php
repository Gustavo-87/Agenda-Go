@props(['negocios'])

@if ($negocios->isEmpty())
    <x-empty-state icon="briefcase" title="Sin negocios registrados" message="Registra un negocio desde el panel de administración." />
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($negocios as $item)
            <a href="?business_id={{ $item->id }}" class="flex items-center gap-3 bg-white rounded-2xl border border-warm-200 shadow-card p-4 hover:border-turquoise-500 transition">
                <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center">
                    <x-icon name="briefcase" />
                </div>
                <div>
                    <div class="font-bold text-petrol">{{ $item->name }}</div>
                    <div class="text-xs text-gray-500">{{ $item->active ? 'Activo' : 'Inactivo' }}</div>
                </div>
            </a>
        @endforeach
    </div>
@endif
