@props(['title', 'subtitle' => null, 'link' => null, 'linkText' => 'সব দেখুন'])

<div class="flex items-end justify-between mb-6">
    <div>
        <h2 class="font-heading font-bold text-xl md:text-2xl text-textprimary">{{ $title }}</h2>
        @if($subtitle)
        <p class="text-sm text-textsecondary mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if($link)
    <a href="{{ $link }}"
       class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-accent transition-colors flex-shrink-0 ml-4">
        {{ $linkText }}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endif
</div>
