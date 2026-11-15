@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-petrol']) }}>
    @if ($value ?? null)
        {{ $value }}
    @else
        {{ $slot }}
    @endif
</label>
