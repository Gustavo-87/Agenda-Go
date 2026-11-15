@props(['href', 'active' => false, 'icon' => null])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 ps-3 pe-4 py-2.5 rounded-xl text-sm font-semibold transition '.($active ? 'bg-turquoise-600 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white')]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="w-5 h-5" />
    @endif
    {{ $slot }}
</a>
