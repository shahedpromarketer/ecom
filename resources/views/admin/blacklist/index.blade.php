@extends('layouts.admin')

@section('title', 'Blacklisted Phones')
@section('page-title', 'Blacklisted Phones')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Blacklist</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif
@if($errors->any())
    <div class="mb-5 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- Info --}}
<div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
    <p class="text-sm text-amber-700 dark:text-amber-400">Blocked phone numbers cannot place orders on the storefront. The restriction is checked in real time at checkout.</p>
</div>

{{-- Add form --}}
<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Add to Blacklist</h3>
    </x-slot:header>
    <form method="POST" action="{{ route('admin.blacklist.store') }}" class="p-6">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" maxlength="11"
                   class="flex-1 min-w-[160px] px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 font-mono">
            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Reason (optional)…"
                   class="flex-[2] min-w-[200px] px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            @csrf
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors active:scale-95">
                Add to Blacklist
            </button>
        </div>
    </form>
</x-admin.card>

{{-- Blacklist table --}}
<div class="mt-6">
<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">
            Blacklisted Numbers
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $phones->total() }}</span>
        </h3>
    </x-slot:header>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Added By</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($phones as $entry)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 font-mono font-semibold text-textprimary dark:text-gray-100">{{ $entry->phone }}</td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $entry->reason ?: '—' }}</td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $entry->blacklistedByUser?->name ?? 'System' }}</td>
                    <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400">{{ $entry->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.blacklist.destroy', $entry) }}"
                              onsubmit="return confirm('Remove {{ $entry->phone }} from blacklist?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="px-2.5 py-1 text-xs font-medium text-danger hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No blacklisted numbers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($phones->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $phones->links() }}
    </div>
    @endif
</x-admin.card>
</div>

@endsection
