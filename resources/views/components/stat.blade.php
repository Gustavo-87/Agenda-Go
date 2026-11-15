@props(['title', 'value', 'icon' => 'chart', 'hint' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-warm-200 shadow-card p-5 flex items-start justify-between']) }}>
    <div>
        <div class="text-sm font-medium text-gray-500">{{ $title }}</div>
        <div class="text-3xl font-extrabold text-petrol mt-1">{{ $value }}</div>
        @if ($hint)
            <div class="text-xs text-gray-400 mt-1">{{ $hint }}</div>
        @endif
    </div>
    <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center">
        <x-icon :name="$icon" class="w-5 h-5" />
    </div>
</div>
