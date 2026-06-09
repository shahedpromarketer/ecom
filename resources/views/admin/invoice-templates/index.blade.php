@extends('layouts.admin')
@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#1B2A4A] dark:text-white">Invoice Templates</h2>
        @if($active)
        <span class="text-sm text-gray-500 dark:text-gray-400">Active: <span class="font-semibold text-amber-600">{{ $active->name }}</span></span>
        @endif
    </div>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $template)
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-5 border-2 transition-all {{ $template->is_active ? 'border-amber-400' : 'border-transparent hover:border-gray-200 dark:hover:border-gray-700' }}">

            {{-- Preview Box --}}
            <div class="rounded-xl overflow-hidden mb-4 bg-gray-50 dark:bg-gray-800 flex items-center justify-center
                {{ $template->size === 'small' ? 'h-32' : ($template->size === 'medium' ? 'h-44' : 'h-56') }}">
                @if($template->size === 'small')
                <div class="bg-white shadow-md rounded p-2 text-center" style="width:90px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;padding:3px;border-radius:3px;margin-bottom:4px;font-size:8px;font-weight:bold;">Clothonic</div>
                    @else
                    <div style="font-size:10px;font-weight:bold;border-bottom:1px dashed #ccc;padding-bottom:3px;margin-bottom:3px;">Clothonic</div>
                    @endif
                    <div style="font-size:7px;color:#888;">Order #00001</div>
                    <div style="font-size:7px;color:#555;">Product x1 ৳500</div>
                    <div style="font-size:8px;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;padding-top:3px;">Total: ৳500</div>
                </div>
                @elseif($template->size === 'medium')
                <div class="bg-white shadow-md rounded p-3" style="width:150px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;text-align:center;padding:5px;border-radius:4px;margin-bottom:6px;">
                        <div style="font-size:10px;font-weight:bold;">Clothonic</div>
                        <div style="font-size:7px;opacity:.7;">INVOICE</div>
                    </div>
                    @else
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid #eee;">
                        <span style="font-size:10px;font-weight:bold;color:#1B2A4A;">Clothonic</span>
                        <span style="font-size:8px;color:#888;">INVOICE</span>
                    </div>
                    @endif
                    <div style="font-size:7px;color:#666;margin-bottom:4px;">Customer: John Doe</div>
                    <div style="display:flex;justify-content:space-between;font-size:7px;"><span>Product</span><span>৳1,200</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:8px;font-weight:bold;border-top:1px solid #eee;margin-top:4px;padding-top:3px;"><span>Total</span><span>৳1,200</span></div>
                </div>
                @else
                <div class="bg-white shadow-md rounded overflow-hidden" style="width:190px;">
                    @if($template->style === 'professional')
                    <div style="background:#1B2A4A;color:white;padding:8px;text-align:center;">
                        <div style="font-size:11px;font-weight:bold;">Clothonic</div>
                        <div style="font-size:7px;opacity:.7;">Official Invoice</div>
                    </div>
                    @else
                    <div style="display:flex;justify-content:space-between;padding:6px 8px;border-bottom:1px solid #eee;">
                        <span style="font-size:11px;font-weight:bold;color:#1B2A4A;">Clothonic</span>
                        <span style="font-size:8px;color:#888;">INVOICE</span>
                    </div>
                    @endif
                    <div style="padding:6px 8px;">
                        <div style="font-size:7px;color:#666;margin-bottom:4px;">Customer: John Doe</div>
                        <table style="width:100%;font-size:6px;border-collapse:collapse;margin-bottom:4px;">
                            <tr style="background:{{ $template->style === 'professional' ? '#F59E0B' : '#f3f4f6' }};color:{{ $template->style === 'professional' ? 'white' : '#374151' }};">
                                <th style="padding:2px 3px;text-align:left;">Item</th><th style="padding:2px 3px;">Qty</th><th style="padding:2px 3px;">Price</th>
                            </tr>
                            <tr><td style="padding:2px 3px;">Product A</td><td style="padding:2px 3px;text-align:center;">1</td><td style="padding:2px 3px;text-align:right;">৳1,200</td></tr>
                        </table>
                        <div style="display:flex;justify-content:space-between;font-size:8px;font-weight:bold;border-top:1px solid #eee;padding-top:3px;"><span>Total</span><span>৳1,200</span></div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="font-semibold text-gray-800 dark:text-white text-sm">{{ $template->name }}</h4>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span class="px-2 py-0.5 rounded-lg text-xs font-medium
                            {{ $template->size === 'small' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : ($template->size === 'medium' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400') }}">
                            {{ ucfirst($template->size) }} ({{ $template->size === 'small' ? '80mm' : ($template->size === 'medium' ? 'A5' : 'A4') }})
                        </span>
                        <span class="px-2 py-0.5 rounded-lg text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ ucfirst($template->style) }}</span>
                    </div>
                </div>
                @if($template->is_active)
                <span class="px-2 py-1 bg-amber-400 text-white rounded-xl text-xs font-bold flex-shrink-0">Active</span>
                @endif
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.invoices.preview', $template->id) }}" target="_blank"
                    class="flex-1 text-center border border-gray-200 dark:border-gray-700 py-2 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Preview
                </a>
                @if(!$template->is_active)
                <form action="{{ route('admin.invoice-templates.activate', $template->id) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-[#1B2A4A] text-white py-2 rounded-xl text-xs font-medium hover:bg-[#243a63] transition">
                        Set Active
                    </button>
                </form>
                @else
                <button disabled class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-400 py-2 rounded-xl text-xs cursor-not-allowed">
                    Current
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
