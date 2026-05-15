@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-border dark:border-gray-700 overflow-hidden ' . $class]) }}>
    @isset($header)
        <div class="px-6 py-4 border-b border-border dark:border-gray-700 flex items-center justify-between gap-4">
            {{ $header }}
        </div>
    @endisset
    {{ $slot }}
</div>
