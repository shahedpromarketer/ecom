@extends('layouts.frontend')

@section('title', 'অর্ডার সফল হয়েছে! — ' . ($settings['store_name'] ?? 'Clothonic'))

@section('content')

<div class="min-h-screen bg-[#F8F8F8] flex flex-col items-center justify-start py-10 px-4">

    {{-- Success Card --}}
    <div class="w-full max-w-lg">

        {{-- Animated Checkmark --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-green-100 mb-4 shadow-lg"
                 style="animation: popIn 0.5s cubic-bezier(.36,.07,.19,.97) both;">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="animation: drawCheck 0.4s 0.3s ease forwards; stroke-dasharray:30; stroke-dashoffset:30;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-textprimary mb-1">
                অর্ডার সফল হয়েছে! 🎉
            </h1>
            <p class="text-textsecondary text-sm sm:text-base">
                আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।
            </p>
        </div>

        {{-- Order Details Card --}}
        @if($order)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">

            {{-- Header --}}
            <div class="bg-primary px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-xs font-medium uppercase tracking-wide">অর্ডার নম্বর</p>
                    <p class="text-white font-heading font-bold text-lg tracking-wide">{{ $order->order_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-white/70 text-xs font-medium uppercase tracking-wide">তারিখ</p>
                    <p class="text-white font-semibold text-sm">{{ $order->created_at->format('d M Y') }}</p>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-xs font-bold text-textsecondary uppercase tracking-wide mb-3">ডেলিভারি তথ্য</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-textsecondary mb-0.5">নাম</p>
                        <p class="text-sm font-semibold text-textprimary">{{ $order->shipping_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-textsecondary mb-0.5">মোবাইল</p>
                        <p class="text-sm font-semibold text-textprimary">{{ $order->shipping_phone }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-textsecondary mb-0.5">ঠিকানা</p>
                        <p class="text-sm font-semibold text-textprimary">{{ $order->shipping_address }}</p>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            @if($order->items->isNotEmpty())
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-xs font-bold text-textsecondary uppercase tracking-wide mb-3">পণ্যের তালিকা</p>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-textprimary leading-snug">{{ $item->product_name }}</p>
                            @if($item->variant_info)
                            <p class="text-xs text-textsecondary mt-0.5">সাইজ: {{ $item->variant_info }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs text-textsecondary">{{ $item->qty }} × ৳{{ number_format($item->unit_price, 0) }}</p>
                            <p class="text-sm font-bold text-textprimary">৳{{ number_format($item->total, 0) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Payment Summary --}}
            <div class="px-6 py-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-textsecondary">পণ্য মূল্য</span>
                        <span class="font-medium text-textprimary">৳{{ number_format($order->subtotal, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-textsecondary">ডেলিভারি চার্জ</span>
                        <span class="font-medium text-textprimary">
                            {{ $order->shipping_charge > 0 ? '৳' . number_format($order->shipping_charge, 0) : 'ফ্রি' }}
                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 flex justify-between">
                        <span class="font-bold text-textprimary">মোট পরিশোধযোগ্য</span>
                        <span class="font-extrabold text-lg text-accent">৳{{ number_format($order->total, 0) }}</span>
                    </div>
                </div>

                {{-- Payment Method Badge --}}
                <div class="mt-4 flex items-center gap-2 bg-blue-50 rounded-xl px-4 py-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-blue-700 font-medium">পেমেন্ট পদ্ধতি: ক্যাশ অন ডেলিভারি</p>
                </div>
            </div>
        </div>
        @else
        {{-- Generic success (no order found) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-8 text-center mb-4">
            <p class="text-textsecondary text-sm">আপনার অর্ডার গ্রহণ করা হয়েছে। আমরা শীঘ্রই যোগাযোগ করব।</p>
        </div>
        @endif

        {{-- Info Steps --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-5 mb-4">
            <p class="text-xs font-bold text-textsecondary uppercase tracking-wide mb-4">এরপর কী হবে?</p>
            <div class="space-y-3">
                @foreach([
                    ['icon'=>'📞','text'=>'আমাদের টিম আপনাকে কনফার্মেশনের জন্য ফোন করবে।'],
                    ['icon'=>'📦','text'=>'অর্ডার প্যাক করে কুরিয়ারে পাঠানো হবে।'],
                    ['icon'=>'🚚','text'=>'২৪–৭২ ঘণ্টার মধ্যে আপনার দরজায় পৌঁছাবে।'],
                    ['icon'=>'💵','text'=>'পণ্য হাতে পেলে পেমেন্ট করুন।'],
                ] as $step)
                <div class="flex items-start gap-3">
                    <span class="text-lg leading-none flex-shrink-0 mt-0.5">{{ $step['icon'] }}</span>
                    <p class="text-sm text-textsecondary leading-relaxed">{{ $step['text'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="{{ route('home') }}"
               class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary text-white font-semibold rounded-xl hover:bg-opacity-90 active:scale-95 transition-all text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                হোমে ফিরে যান
            </a>

            @if($settings['whatsapp_phone'])
            <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $settings['whatsapp_phone']) }}?text={{ urlencode('আমার অর্ডার নম্বর: ' . ($order->order_number ?? '')) }}"
               target="_blank" rel="noopener"
               class="flex items-center justify-center gap-2 px-6 py-3.5 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 active:scale-95 transition-all text-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                WhatsApp করুন
            </a>
            @else
            <a href="{{ route('home') }}"
               class="flex items-center justify-center gap-2 px-6 py-3.5 bg-accent text-white font-semibold rounded-xl hover:bg-yellow-500 active:scale-95 transition-all text-sm">
                আরও কেনাকাটা করুন
            </a>
            @endif
        </div>

    </div>
</div>

<style>
@keyframes popIn {
    0%   { transform: scale(0.5); opacity: 0; }
    80%  { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes drawCheck {
    to { stroke-dashoffset: 0; }
}
</style>

@endsection
