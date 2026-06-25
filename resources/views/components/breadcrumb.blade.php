@props(['items' => []])

<nav class="flex items-center gap-1 text-sm text-textsecondary" aria-label="Breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
    </a>
    @foreach($items as $label => $url)
    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    @if($loop->last)
    <span class="text-textprimary font-medium truncate max-w-[200px]">{{ $label }}</span>
    @else
    <a href="{{ $url }}" class="hover:text-primary transition-colors truncate max-w-[150px]">{{ $label }}</a>
    @endif
    @endforeach
</nav>
