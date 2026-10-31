{{-- Courier Success Rate body (loaded into the order page). Needs $check from CustomerCheck::for(). --}}
@php
    use App\Services\CustomerCheck\CustomerCheck as CC;
    $bar = ['green' => 'bg-green-500', 'yellow' => 'bg-amber-500', 'red' => 'bg-red-500', 'gray' => 'bg-gray-300 dark:bg-gray-600'];
    $txt = ['green' => 'text-green-600', 'yellow' => 'text-amber-600', 'red' => 'text-red-600', 'gray' => 'text-gray-400'];
    $ring = ['green' => '#16a34a', 'yellow' => '#d97706', 'red' => '#dc2626', 'gray' => '#d1d5db'];
    $circle = function (array $r, string $title, string $sub = '') use ($ring) {
        $rate = $r['rate'] ?? CC::rate($r);
        $tone = CC::tone($rate);
        $deg = $rate !== null ? round($rate * 3.6) : 0;
        return compact('rate', 'tone', 'deg', 'title', 'sub') + ['r' => $r, 'color' => $ring[$tone]];
    };
@endphp

@if (! empty($check['invalid']))
    <p class="text-sm text-gray-500">No valid mobile number on this order.</p>
@else
<div class="grid lg:grid-cols-[1fr_auto] gap-5 items-start">
    <div class="overflow-x-auto">
        <table class="w-full text-sm border border-gray-200 dark:border-gray-700">
            <thead><tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                <th class="text-left p-2 w-32">Courier</th><th class="text-left p-2">Score</th></tr></thead>
            <tbody>
            @foreach ($check['couriers'] as $key => $c)
                @php
                    $hasCounts = $c['total'] !== null;
                    $rate = $hasCounts ? CC::rate($c) : null;
                    $tone = CC::tone($rate);
                @endphp
                <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                    <td class="p-2 font-semibold text-gray-800 dark:text-gray-200">{{ $c['label'] }}
                        @if (! empty($c['reports']))<div class="text-[11px] font-normal text-red-600">{{ $c['reports'] }} merchant report(s)</div>@endif</td>
                    <td class="p-2">
                        @if ($hasCounts)
                            <div class="relative h-4 rounded {{ $c['total'] ? 'bg-gray-200 dark:bg-gray-700' : $bar['gray'] }} overflow-hidden max-w-xs">
                                @if ($c['total'])<div class="h-4 {{ $bar[$tone] }}" style="width: {{ $rate }}%"></div>
                                <span class="absolute inset-0 text-[10px] leading-4 text-center text-white font-semibold">{{ rtrim(rtrim(number_format($rate, 1), '0'), '.') }}%</span>@endif
                            </div>
                            <div class="text-xs mt-1 flex gap-4">
                                <span>To: <b>{{ $c['total'] }}</b></span>
                                <span class="text-green-600">Su: <b>{{ $c['success'] }}</b></span>
                                <span class="text-red-500">Fa: <b>{{ $c['failed'] }}</b></span>
                                @if ($c['rating'])<span class="text-gray-500">· {{ $c['rating'] }}</span>@endif
                            </div>
                        @elseif ($c['rating'])
                            @php $rt = ['Excellent' => 'green', 'Good' => 'green', 'Moderate' => 'yellow', 'Risky' => 'red'][$c['rating']] ?? 'gray'; @endphp
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $txt[$rt] }} bg-gray-100 dark:bg-gray-800">{{ $c['rating'] }}</span>
                            <span class="text-[11px] text-gray-400 ml-1">({{ $c['label'] }} shows a rating, not counts)</span>
                        @else
                            <span class="text-xs text-gray-400">{{ $c['note'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="text-[11px] text-gray-400 mt-2">NB: Ecom Pilot's own count is not added to "All" (the same parcels may already be in the couriers' counts). Checked {{ \Carbon\Carbon::parse($check['checked'])->diffForHumans() }}.</p>
    </div>

    <div class="flex flex-wrap justify-center gap-6">
        @foreach ([$circle($check['own'], 'Ecom Pilot', 'This shop: ' . $check['shop']['success'] . '/' . $check['shop']['total']), $circle($check['all'], 'All')] as $ci)
            <div class="text-center w-36">
                <div class="mx-auto w-24 h-24 rounded-full grid place-items-center" style="background: conic-gradient({{ $ci['color'] }} {{ $ci['deg'] }}deg, #e5e7eb 0)">
                    <div class="w-[78px] h-[78px] rounded-full bg-white dark:bg-gray-900 grid place-items-center">
                        <span class="text-lg font-bold {{ $txt[$ci['tone']] }}">{{ $ci['rate'] !== null ? rtrim(rtrim(number_format($ci['rate'], 1), '0'), '.') . '%' : '—' }}</span>
                    </div>
                </div>
                <p class="mt-2 font-bold text-gray-800 dark:text-gray-200">{{ $ci['title'] }}</p>
                <p class="text-xs text-gray-500">Total: {{ $ci['r']['total'] }} · Success: {{ $ci['r']['success'] }} · Failed: {{ $ci['r']['failed'] }}</p>
                @if ($ci['sub'])<p class="text-[11px] text-gray-400">{{ $ci['sub'] }}</p>@endif
            </div>
        @endforeach
    </div>
</div>
@endif
