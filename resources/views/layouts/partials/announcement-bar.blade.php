<div x-show="announcementVisible" x-transition:leave="transition duration-300" x-transition:leave-start="opacity-100 max-h-9" x-transition:leave-end="opacity-0 max-h-0"
    class="relative bg-primary text-white overflow-hidden h-9 flex items-center">
    <div class="flex-1 overflow-hidden">
        <div class="whitespace-nowrap animate-marquee text-xs font-medium inline-block">
            🚚 সারাদেশে ফ্রি ডেলিভারি ৳{{ $settings['free_shipping_above'] ?? 999 }} এর উপরে &nbsp;&nbsp;|&nbsp;&nbsp;
            💵 ক্যাশ অন ডেলিভারি &nbsp;&nbsp;|&nbsp;&nbsp;
            🔄 ৭ দিনের রিটার্ন পলিসি &nbsp;&nbsp;|&nbsp;&nbsp;
            ✅ ১০০% অরিজিনাল পণ্য &nbsp;&nbsp;|&nbsp;&nbsp;
            📞 {{ $settings['store_phone'] ?? '' }} &nbsp;&nbsp;|&nbsp;&nbsp;
            🚚 সারাদেশে ফ্রি ডেলিভারি ৳{{ $settings['free_shipping_above'] ?? 999 }} এর উপরে &nbsp;&nbsp;|&nbsp;&nbsp;
            💵 ক্যাশ অন ডেলিভারি &nbsp;&nbsp;|&nbsp;&nbsp;
            🔄 ৭ দিনের রিটার্ন পলিসি &nbsp;&nbsp;|&nbsp;&nbsp;
            ✅ ১০০% অরিজিনাল পণ্য
        </div>
    </div>
    <button @click="announcementVisible = false"
        class="flex-shrink-0 px-3 py-1 hover:bg-white/10 transition-colors ml-2"
        aria-label="Close">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
