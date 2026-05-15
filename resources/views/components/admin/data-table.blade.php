<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-border dark:border-gray-700']) }}>
    <table class="data-table">
        {{ $slot }}
    </table>
</div>
