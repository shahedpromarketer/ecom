@extends('layouts.admin')

@section('title', 'Courier payment')
@section('page-title', 'Courier payment — ' . ($payment->reference ?: $payment->file_name))

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.courier-payments.index') }}" class="text-textsecondary hover:text-textprimary">Courier payments</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Details</span>
@endsection

@section('content')
<div class="max-w-5xl space-y-4" x-data="{ only: 'all' }">
    @if (session('success'))<div class="p-3 rounded-xl bg-green-50 text-green-800 border border-green-200 text-sm">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
        @foreach ([['Collected', '৳' . number_format($payment->total_collected)], ['Courier charge', '৳' . number_format($payment->total_charge)],
                   ['Matched', $payment->matched], ['Different amount', $payment->mismatched], ['Not found', $payment->unknown]] as [$l, $v])
            <div class="p-3 rounded-2xl bg-white dark:bg-gray-800 border border-border dark:border-gray-700">
                <p class="text-xs text-textsecondary">{{ $l }}</p><p class="text-lg font-bold">{{ $v }}</p></div>
        @endforeach
    </div>

    <div class="flex gap-2 text-sm">
        @foreach (['all' => 'All', 'mismatched' => 'Different amount', 'unknown' => 'Not found', 'matched' => 'Matched'] as $k => $l)
            <button @click="only = '{{ $k }}'" class="px-3 py-1.5 rounded-xl" :class="only === '{{ $k }}' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 border border-border'">{{ $l }}</button>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-textsecondary border-b border-border dark:border-gray-700">
                <th class="p-3">Courier id</th><th>Order</th><th class="text-right">Paid</th><th class="text-right">Expected</th><th class="text-right">Charge</th><th class="pl-3">Result</th></tr></thead>
            <tbody>
                @foreach ($payment->rows ?? [] as $r)
                    <tr class="border-b border-border dark:border-gray-700" x-show="only === 'all' || only === '{{ $r['result'] }}'">
                        <td class="p-3 font-mono text-xs">{{ $r['id'] }}</td>
                        <td>@if (! empty($r['order_id']))<a class="text-primary" href="{{ route('admin.orders.show', $r['order_id']) }}">#{{ $r['order'] }}</a>@else — @endif</td>
                        <td class="text-right">{{ isset($r['collected']) ? '৳' . number_format($r['collected']) : '—' }}</td>
                        <td class="text-right">{{ isset($r['expected']) ? '৳' . number_format($r['expected']) : '—' }}</td>
                        <td class="text-right">{{ isset($r['charge']) ? '৳' . number_format($r['charge']) : '—' }}</td>
                        <td class="pl-3 {{ ['matched' => 'text-green-700', 'mismatched' => 'text-red-600 font-semibold', 'unknown' => 'text-amber-600'][$r['result']] ?? '' }}">{{ $r['message'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
