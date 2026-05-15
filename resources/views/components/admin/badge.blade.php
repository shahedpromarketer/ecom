@props(['color' => 'bg-gray-100 text-gray-800', 'text'])

<span {{ $attributes->merge(['class' => 'admin-badge ' . $color]) }}>
    {{ $text }}
</span>
