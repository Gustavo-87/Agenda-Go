@props(['type' => 'text'])

<input {{ $attributes->merge(['type' => $type, 'class' => 'border-warm-300 bg-white focus:border-turquoise-500 focus:ring-turquoise-500 rounded-xl shadow-sm text-sm text-petrol placeholder-gray-400']) }}>
