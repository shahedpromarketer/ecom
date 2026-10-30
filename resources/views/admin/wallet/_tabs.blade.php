<div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-800 text-sm">
    <a href="{{ route('admin.wallet.index') }}"
       class="px-4 py-1.5 rounded-lg font-semibold {{ $active === 'overview' ? 'bg-white dark:bg-gray-700 shadow-sm text-textprimary dark:text-white' : 'text-textsecondary hover:text-textprimary' }}">Overview</a>
    <a href="{{ route('admin.wallet.history') }}"
       class="px-4 py-1.5 rounded-lg font-semibold {{ $active === 'history' ? 'bg-white dark:bg-gray-700 shadow-sm text-textprimary dark:text-white' : 'text-textsecondary hover:text-textprimary' }}">History</a>
</div>
