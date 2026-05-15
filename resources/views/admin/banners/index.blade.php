@extends('layouts.admin')

@section('title', 'Banners & Sliders')
@section('page-title', 'Banners & Sliders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Banners</span>
@endsection

@section('content')

{{-- Flash alerts --}}
@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
@endif

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-heading font-semibold text-textprimary dark:text-gray-100">Banners & Sliders</h2>
        <p class="text-sm text-textsecondary dark:text-gray-400 mt-0.5">Manage homepage and section banners</p>
    </div>
    <a href="{{ route('admin.banners.create') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Banner
    </a>
</div>

@if($banners->isEmpty())
    {{-- Empty state --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm">
        <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-textprimary dark:text-gray-100 mb-1">No banners yet</h3>
            <p class="text-sm text-textsecondary dark:text-gray-400 mb-5">Create your first banner to display on the storefront.</p>
            <a href="{{ route('admin.banners.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Banner
            </a>
        </div>
    </div>
@else
    {{-- Banners grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($banners as $banner)
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">

                {{-- Image preview --}}
                <div class="aspect-[16/6] w-full bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
                    @if($banner->image && \Illuminate\Support\Facades\Storage::exists($banner->image))
                        <img src="{{ Storage::url($banner->image) }}"
                             alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Card body --}}
                <div class="p-4 flex flex-col gap-2 flex-1">

                    {{-- Title + position badge --}}
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-sm text-textprimary dark:text-gray-100 leading-snug">{{ $banner->title }}</h3>
                        @php
                            $positionBadge = match($banner->position) {
                                'hero'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'section' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'popup'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                default   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            };
                            $positionLabel = match($banner->position) {
                                'hero'    => 'Hero',
                                'section' => 'Section',
                                'popup'   => 'Popup',
                                default   => ucfirst($banner->position),
                            };
                        @endphp
                        <span class="flex-shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $positionBadge }}">
                            {{ $positionLabel }}
                        </span>
                    </div>

                    {{-- Subtitle --}}
                    @if($banner->subtitle)
                        <p class="text-xs text-textsecondary dark:text-gray-400 leading-relaxed line-clamp-2">{{ $banner->subtitle }}</p>
                    @endif

                    {{-- Sort order --}}
                    <p class="text-xs text-textsecondary dark:text-gray-500">Sort: {{ $banner->sort_order }}</p>

                    {{-- Actions row --}}
                    <div class="flex items-center justify-between gap-2 mt-auto pt-3 border-t border-border dark:border-gray-700">

                        {{-- Status toggle (Alpine.js PATCH) --}}
                        <div x-data="{ active: {{ $banner->is_active ? 'true' : 'false' }} }">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <button type="button"
                                    @click="fetch('{{ route('admin.banners.toggle', $banner) }}', {
                                        method: 'PATCH',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    }).then(r => r.json()).then(d => active = d.is_active)"
                                    :class="active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none">
                                    <span :class="active ? 'translate-x-4' : 'translate-x-1'"
                                          class="inline-block h-4 w-4 bg-white rounded-full transform transition-transform shadow-sm"></span>
                                </button>
                                <span class="text-xs text-textsecondary dark:text-gray-400" x-text="active ? 'Active' : 'Inactive'"></span>
                            </label>
                        </div>

                        {{-- Edit + Delete --}}
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.banners.edit', $banner) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                Edit
                            </a>
                            <form method="POST"
                                  action="{{ route('admin.banners.destroy', $banner) }}"
                                  onsubmit="return confirm('Delete this banner? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2.5 py-1 text-xs font-medium border border-red-200 dark:border-red-800 text-danger rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
