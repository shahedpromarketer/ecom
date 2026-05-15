<div x-data="{ open: false, url: '' }"
     x-on:open-confirm-delete.window="open = true; url = $event.detail.url">

    {{-- Backdrop --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 dark:bg-black/70 z-50 flex items-center justify-center p-4">

        {{-- Modal box --}}
        <div x-show="open"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6
                    border border-border dark:border-gray-700">

            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-danger dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Delete Confirmation</h3>
                    <p class="text-sm text-textsecondary dark:text-gray-400 mt-1">Are you sure you want to delete this item? This action cannot be undone.</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        @click="open = false"
                        class="flex-1 px-4 py-2 text-sm font-medium
                               text-textprimary dark:text-gray-200
                               bg-muted dark:bg-gray-700
                               border border-border dark:border-gray-600
                               rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>

                <form :action="url" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2 text-sm font-medium text-white
                                   bg-danger hover:bg-red-600 dark:hover:bg-red-700
                                   rounded-lg transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
