@props(['type' => 'button'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'inline-flex items-center gap-2 px-4 py-2.5 bg-turquoise-600 border border-transparent rounded-xl font-semibold text-xs sm:text-sm text-white uppercase tracking-wide hover:bg-turquoise-700 focus:outline-none focus:ring-2 focus:ring-turquoise-500 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
