@props(['type' => 'submit'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-warm-300 rounded-xl font-semibold text-xs sm:text-sm text-petrol hover:bg-warm-100 focus:outline-none focus:ring-2 focus:ring-turquoise-500 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
