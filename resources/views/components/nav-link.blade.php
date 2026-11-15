@props(['href', 'active' => false])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition '.($active ? 'border-turquoise-500 text-white' : 'border-transparent text-white/60 hover:text-white hover:border-white/30')]) }}>
    {{ $slot }}
</a>
