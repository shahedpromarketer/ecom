@props(['title', 'value', 'color' => 'blue'])

@php
$palette = [
    'blue'   => ['bg' => 'bg-blue-50   dark:bg-blue-900/20',   'text' => 'text-blue-600   dark:text-blue-400'],
    'green'  => ['bg' => 'bg-green-50  dark:bg-green-900/20',  'text' => 'text-green-600  dark:text-green-400'],
    'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400'],
    'amber'  => ['bg' => 'bg-amber-50  dark:bg-amber-900/20',  'text' => 'text-amber-600  dark:text-amber-400'],
];
$c = $palette[$color] ?? $palette['blue'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-border dark:border-gray-700 p-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center {{ $c['bg'] }} {{ $c['text'] }}">
        {{ $icon }}
    </div>
    <div class="min-w-0">
        <p class="text-sm text-textsecondary dark:text-gray-400 truncate">{{ $title }}</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100 font-heading mt-0.5">{{ $value }}</p>
    </div>
</div>
