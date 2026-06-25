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
        @php $isActive = $activeId && $template->id === (int)$activeId; @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-5 border-2 transition-all {{ $isActive ? 'border-amber-400' : 'border-transparent hover:border-gray-200 dark:hover:border-gray-700' }}">

            {{-- Preview Box --}}
            @php
                $fixedSizes = ['small','2inch','3inch','3x2','3x3','3x4','4x4'];
                $boxHeight = in_array($template->size, ['small','2inch','3inch','3x2']) ? 'h-28'
                    : (in_array($template->size, ['3x3','3x4','4x4']) ? 'h-36'
                    : ($template->size === 'medium' ? 'h-44' : 'h-56'));
            @endphp
            <div class="rounded-xl overflow-hidden mb-4 bg-gray-50 dark:bg-gray-800 flex items-center justify-center {{ $boxHeight }}">
                @if($template->size === '3x2')
                <div class="bg-white shadow-md rounded overflow-hidden" style="width:120px;height:80px;font-size:6px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;padding:3px 4px;font-weight:bold;font-size:7px;">Ecom Pilot</div>
                    @else
                    <div style="font-size:7px;font-weight:bold;border-bottom:1px dashed #ccc;padding:2px 4px;">Ecom Pilot</div>
                    @endif
                    <div style="padding:2px 4px;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Inv:</span><span>#001</span></div>
                        <div style="display:flex;justify-content:space-between;margin-top:1px;"><span>Product A x1</span><span>৳500</span></div>
                        <div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px dashed #ccc;margin-top:2px;padding-top:1px;"><span>Total</span><span>৳500</span></div>
                    </div>
                </div>
                @elseif($template->size === '3x3')
                <div class="bg-white shadow-md rounded overflow-hidden" style="width:110px;height:110px;font-size:6.5px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;text-align:center;padding:4px;font-weight:bold;font-size:8px;">Ecom Pilot</div>
                    @else
                    <div style="font-size:8px;font-weight:bold;border-bottom:1px dashed #ccc;padding:3px 4px;text-align:center;">Ecom Pilot</div>
                    @endif
                    <div style="padding:3px 4px;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Invoice:</span><span>#00001</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Name:</span><span>John Doe</span></div>
                        <div style="display:flex;justify-content:space-between;margin-top:2px;"><span>Product A</span><span>৳500</span></div>
                        <div style="display:flex;justify-content:space-between;margin-top:1px;"><span>Product B</span><span>৳300</span></div>
                        <div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px dashed #ccc;margin-top:2px;padding-top:2px;"><span>Total</span><span>৳800</span></div>
                    </div>
                </div>
                @elseif($template->size === '3x4')
                <div class="bg-white shadow-md rounded overflow-hidden" style="width:100px;height:133px;font-size:6.5px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;text-align:center;padding:4px;font-weight:bold;font-size:8px;">Ecom Pilot</div>
                    @else
                    <div style="font-size:8px;font-weight:bold;border-bottom:1px dashed #ccc;padding:3px 5px;text-align:center;">Ecom Pilot</div>
                    @endif
                    <div style="padding:3px 5px;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Invoice:</span><span>#00001</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Name:</span><span>John Doe</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Phone:</span><span>01700000</span></div>
                        <div style="border-bottom:1px dashed #ccc;margin:2px 0;"></div>
                        <div style="display:flex;justify-content:space-between;"><span>Product A</span><span>৳500</span></div>
                        <div style="display:flex;justify-content:space-between;margin-top:1px;"><span>Product B</span><span>৳300</span></div>
                        <div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px dashed #ccc;margin-top:2px;padding-top:2px;"><span>Total</span><span>৳800</span></div>
                    </div>
                </div>
                @elseif($template->size === '4x4')
                <div class="bg-white shadow-md rounded overflow-hidden" style="width:130px;height:130px;font-size:6.5px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;text-align:center;padding:5px;font-weight:bold;font-size:8px;">Ecom Pilot<br><span style="font-size:6px;opacity:.7;">INVOICE</span></div>
                    @else
                    <div style="font-size:8px;font-weight:bold;border-bottom:1px dashed #ccc;padding:3px 5px;text-align:center;">Ecom Pilot</div>
                    @endif
                    <div style="padding:3px 5px;">
                        <div style="display:flex;gap:8px;">
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Inv:</span><span>#001</span></div>
                                <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Pay:</span><span>COD</span></div>
                            </div>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Name:</span><span>John</span></div>
                                <div style="display:flex;justify-content:space-between;"><span style="color:#888;">Ph:</span><span>0170</span></div>
                            </div>
                        </div>
                        <div style="border-bottom:1px dashed #ccc;margin:2px 0;"></div>
                        <div style="display:flex;justify-content:space-between;"><span>Product A x1</span><span>৳500</span></div>
                        <div style="display:flex;justify-content:space-between;margin-top:1px;"><span>Product B x2</span><span>৳600</span></div>
                        <div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px dashed #ccc;margin-top:2px;padding-top:2px;"><span>Total</span><span>৳1,100</span></div>
                    </div>
                </div>
                @elseif($template->size === '2inch')
                <div class="bg-white shadow-md rounded p-2 text-center" style="width:76px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;padding:3px;border-radius:3px;margin-bottom:4px;font-size:7px;font-weight:bold;">Ecom Pilot</div>
                    @else
                    <div style="font-size:9px;font-weight:bold;border-bottom:1px dashed #ccc;padding-bottom:3px;margin-bottom:3px;">Ecom Pilot</div>
                    @endif
                    <div style="font-size:6.5px;color:#888;">Order #00001</div>
                    <div style="font-size:6.5px;color:#555;">Product x1 ৳500</div>
                    <div style="font-size:7.5px;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;padding-top:3px;">Total: ৳500</div>
                </div>
                @elseif($template->size === '3inch')
                <div class="bg-white shadow-md rounded p-2 text-center" style="width:100px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;padding:4px;border-radius:3px;margin-bottom:4px;font-size:8px;font-weight:bold;">Ecom Pilot</div>
                    @else
                    <div style="font-size:10px;font-weight:bold;border-bottom:1px dashed #ccc;padding-bottom:3px;margin-bottom:3px;">Ecom Pilot</div>
                    @endif
                    <div style="font-size:7px;color:#888;">Order #00001</div>
                    <div style="font-size:7px;color:#555;">Product A x1 ৳500</div>
                    <div style="font-size:8px;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;padding-top:3px;">Total: ৳500</div>
                </div>
                @elseif($template->size === 'small')
                <div class="bg-white shadow-md rounded p-2 text-center" style="width:90px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;padding:3px;border-radius:3px;margin-bottom:4px;font-size:8px;font-weight:bold;">Ecom Pilot</div>
                    @else
                    <div style="font-size:10px;font-weight:bold;border-bottom:1px dashed #ccc;padding-bottom:3px;margin-bottom:3px;">Ecom Pilot</div>
                    @endif
                    <div style="font-size:7px;color:#888;">Order #00001</div>
                    <div style="font-size:7px;color:#555;">Product x1 ৳500</div>
                    <div style="font-size:8px;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;padding-top:3px;">Total: ৳500</div>
                </div>
                @elseif($template->size === 'medium')
                <div class="bg-white shadow-md rounded p-3" style="width:150px;">
                    @if($template->style === 'standard')
                    <div style="background:#1B2A4A;color:white;text-align:center;padding:5px;border-radius:4px;margin-bottom:6px;">
                        <div style="font-size:10px;font-weight:bold;">Ecom Pilot</div>
                        <div style="font-size:7px;opacity:.7;">INVOICE</div>
                    </div>
                    @else
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid #eee;">
                        <span style="font-size:10px;font-weight:bold;color:#1B2A4A;">Ecom Pilot</span>
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
                        <div style="font-size:11px;font-weight:bold;">Ecom Pilot</div>
                        <div style="font-size:7px;opacity:.7;">Official Invoice</div>
                    </div>
                    @else
                    <div style="display:flex;justify-content:space-between;padding:6px 8px;border-bottom:1px solid #eee;">
                        <span style="font-size:11px;font-weight:bold;color:#1B2A4A;">Ecom Pilot</span>
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
                        @php
                            $sizeColors = [
                                'small'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                '2inch'  => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                                '3inch'  => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                                '3x2'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                '3x3'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                '3x4'    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                '4x4'    => 'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400',
                                'medium' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                'large'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            ];
                            $sizeLabels = [
                                'small'  => '80mm Thermal',
                                '2inch'  => '2 Inch (58mm)',
                                '3inch'  => '3 Inch (76mm)',
                                '3x2'    => '3×2 (76×51mm)',
                                '3x3'    => '3×3 (76×76mm)',
                                '3x4'    => '3×4 (76×102mm)',
                                '4x4'    => '4×4 (102×102mm)',
                                'medium' => 'A5',
                                'large'  => 'A4',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-lg text-xs font-medium {{ $sizeColors[$template->size] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $sizeLabels[$template->size] ?? ucfirst($template->size) }}
                        </span>
                        <span class="px-2 py-0.5 rounded-lg text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ ucfirst($template->style) }}</span>
                    </div>
                </div>
                @if($isActive)
                <span class="px-2 py-1 bg-amber-400 text-white rounded-xl text-xs font-bold flex-shrink-0">Active</span>
                @endif
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.invoices.preview', $template->id) }}" target="_blank"
                    class="flex-1 text-center border border-gray-200 dark:border-gray-700 py-2 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Preview
                </a>
                @if(!$isActive)
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
