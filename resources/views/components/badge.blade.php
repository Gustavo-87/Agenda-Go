@props(['status'])

@php
    $styles = [
        'confirmed' => 'bg-emerald-100 text-emerald-800',
        'completed' => 'bg-turquoise-100 text-turquoise-700',
        'pending' => 'bg-amber-100 text-amber-800',
        'cancelled' => 'bg-gray-200 text-gray-600',
        'absent' => 'bg-red-100 text-red-700',
        'expired' => 'bg-gray-100 text-gray-500',
    ];
    $labels = [
        'confirmed' => 'Confirmada',
        'completed' => 'Completada',
        'pending' => 'Pendiente',
        'cancelled' => 'Cancelada',
        'absent' => 'Ausente',
        'expired' => 'Expirada',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold '.($styles[$status] ?? 'bg-gray-100 text-gray-600')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
