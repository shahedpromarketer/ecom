@props(['page'])

{{-- Saved filters bar: reopen a saved set of filters, or save the ones in the URL right now. --}}
@php
    $filters = \App\Models\SavedFilter::availableTo(auth()->user(), $page)->with('user')->get();
    $current = collect(request()->query())->except(['page'])->filter(fn ($v) => $v !== '' && $v !== null && $v !== []);
    $currentQuery = http_build_query($current->all());
@endphp

<div x-data="{ saving: false }" class="mb-3 flex flex-wrap items-center gap-2">
    <span class="text-xs font-semibold text-textsecondary uppercase tracking-wide">Saved filters</span>

    @forelse($filters as $f)
        <span class="inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-xs border
                     {{ $currentQuery !== '' && $currentQuery === $f->query ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-textprimary dark:text-gray-200' }}">
            <a href="{{ $f->url() }}" title="{{ $f->is_shared ? 'Shared by ' . ($f->user?->name ?? 'a colleague') : 'Only you' }}">
                {{ $f->name }}@if($f->is_shared) <span class="opacity-60">· shared</span>@endif
            </a>
            @if($f->user_id === auth()->id() || auth()->user()->hasFullAccess())
                <form method="POST" action="{{ route('admin.saved-filters.destroy', $f) }}" onsubmit="return confirm('Remove saved filter “{{ $f->name }}”?')">
                    @csrf @method('DELETE')
                    <button class="w-5 h-5 rounded-full hover:bg-black/10 leading-none" title="Remove">×</button>
                </form>
            @endif
        </span>
    @empty
        <span class="text-xs text-textsecondary">none yet</span>
    @endforelse

    @if($current->isNotEmpty())
        <button type="button" @click="saving = !saving" class="text-xs text-primary hover:underline">+ Save current filter</button>
        <form method="POST" action="{{ route('admin.saved-filters.store') }}" x-show="saving" x-cloak class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="hidden" name="page" value="{{ $page }}">
            <input type="hidden" name="query" value="{{ $currentQuery }}">
            <input name="name" required maxlength="100" placeholder="e.g. Confirmed – Steadfast – this month"
                   class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 w-64">
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_shared" value="1"> Share with staff</label>
            <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary text-white">Save</button>
        </form>
    @endif
</div>
