@props(['icon' => 'clipboard', 'title' => 'Sin información', 'message' => ''])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-6']) }}>
    <div class="w-14 h-14 rounded-2xl bg-warm-100 text-turquoise-700 flex items-center justify-center mx-auto">
        <x-icon :name="$icon" class="w-7 h-7" />
    </div>
    <h3 class="mt-4 text-base font-semibold text-petrol">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 text-sm text-gray-500 max-w-md mx-auto">{{ $message }}</p>
    @endif
    <div class="mt-4">{{ $slot }}</div>
</div>
