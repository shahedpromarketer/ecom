<footer class="bg-primary text-white mt-16">
    <div class="container-main py-12 md:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-10">

            {{-- Col 1: Brand --}}
            <div class="lg:col-span-1">
                @if($settings['site_logo'])
                    <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                         alt="{{ $settings['store_name'] }}"
                         class="h-12 w-auto object-contain mb-4 brightness-0 invert">
                @else
                    <h3 class="font-heading font-bold text-2xl mb-4">{{ $settings['store_name'] ?? 'Clothonic' }}</h3>
                @endif

                <p class="text-sm text-white/70 leading-relaxed mb-5">
                    বাংলাদেশের সেরা মেনস ক্লোদিং শপ। মানসম্পন্ন পণ্য, সেরা দাম এবং দ্রুত ডেলিভারি।
                </p>

                {{-- Social Icons --}}
                <div class="flex items-center gap-3 mb-6">
                    @if($settings['facebook_url'])
                    <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener"
                       class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    </a>
                    @endif
                    @if($settings['instagram_url'])
                    <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener"
                       class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/><path fill="none" stroke="currentColor" stroke-width="2" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line fill="none" stroke="currentColor" stroke-width="2" x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    @endif
                    @if($settings['youtube_url'])
                    <a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener"
                       class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon fill="#1B2A4A" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
                    </a>
                    @endif
                </div>

                {{-- Contact --}}
                <div class="space-y-2">
                    @if($settings['store_phone'])
                    <a href="tel:{{ $settings['store_phone'] }}" class="flex items-center gap-2 text-sm text-white/70 hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $settings['store_phone'] }}
                    </a>
                    @endif
                    @if($settings['store_email'])
                    <a href="mailto:{{ $settings['store_email'] }}" class="flex items-center gap-2 text-sm text-white/70 hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $settings['store_email'] }}
                    </a>
                    @endif
                </div>
            </div>

            {{-- Col 2: Shop --}}
            <div>
                <h4 class="font-heading font-semibold text-base mb-5">শপিং</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('shop', ['sort' => 'newest']) }}" class="text-sm text-white/70 hover:text-white transition-colors">নতুন পণ্য</a></li>
                    <li><a href="{{ route('shop', ['featured' => 1]) }}" class="text-sm text-white/70 hover:text-white transition-colors">বেস্টসেলার</a></li>
                    <li><a href="{{ route('shop', ['sale' => 1]) }}" class="text-sm text-white/70 hover:text-white transition-colors">সেল</a></li>
                    <li><a href="{{ route('shop') }}" class="text-sm text-white/70 hover:text-white transition-colors">সব পণ্য</a></li>
                    @foreach($categories->take(4) as $cat)
                    <li><a href="{{ route('shop', ['category' => $cat->slug]) }}" class="text-sm text-white/70 hover:text-white transition-colors">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Col 3: Help --}}
            <div>
                <h4 class="font-heading font-semibold text-base mb-5">সাহায্য</h4>
                <ul class="space-y-3">
                    <li><a href="/about" class="text-sm text-white/70 hover:text-white transition-colors">আমাদের সম্পর্কে</a></li>
                    <li><a href="/shipping" class="text-sm text-white/70 hover:text-white transition-colors">শিপিং তথ্য</a></li>
                    <li><a href="/returns" class="text-sm text-white/70 hover:text-white transition-colors">রিটার্ন পলিসি</a></li>
                    <li><a href="/track-order" class="text-sm text-white/70 hover:text-white transition-colors">অর্ডার ট্র্যাক</a></li>
                    <li><a href="/contact" class="text-sm text-white/70 hover:text-white transition-colors">যোগাযোগ</a></li>
                </ul>
            </div>

            {{-- Col 4: Payment --}}
            <div>
                <h4 class="font-heading font-semibold text-base mb-5">পেমেন্ট পদ্ধতি</h4>
                <p class="text-xs text-white/60 mb-3">আমরা গ্রহণ করি:</p>
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="px-3 py-1.5 bg-white/10 rounded-lg text-xs font-semibold text-white">bKash</span>
                    <span class="px-3 py-1.5 bg-white/10 rounded-lg text-xs font-semibold text-white">Nagad</span>
                    <span class="px-3 py-1.5 bg-white/10 rounded-lg text-xs font-semibold text-white">COD</span>
                    <span class="px-3 py-1.5 bg-white/10 rounded-lg text-xs font-semibold text-white">Rocket</span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs text-white/60">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        ১০০% নিরাপদ পেমেন্ট
                    </div>
                    <div class="flex items-center gap-2 text-xs text-white/60">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        ১০০% অরিজিনাল পণ্য গ্যারান্টি
                    </div>
                    <div class="flex items-center gap-2 text-xs text-white/60">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        ৭ দিনের রিটার্ন পলিসি
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-white/10">
        <div class="container-main py-4 flex flex-col md:flex-row items-center justify-between gap-2">
            <p class="text-xs text-white/50 text-center">
                © {{ date('Y') }} {{ $settings['store_name'] ?? 'Clothonic' }}. সর্বস্বত্ব সংরক্ষিত।
            </p>
            <p class="text-xs text-white/40">Powered by Clothonic</p>
        </div>
    </div>
</footer>
