@extends('layouts.admin')

@section('title', 'Courier payments')
@section('page-title', 'Courier payments')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Courier payments</span>
@endsection

@php
    $card  = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm';
    $input = 'w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50';
@endphp

@section('content')
<div class="max-w-5xl space-y-5">
    @foreach (['success' => 'green', 'error' => 'red'] as $k => $c)
        @if (session($k))<div class="p-3 rounded-xl bg-{{ $c }}-50 text-{{ $c }}-800 border border-{{ $c }}-200 text-sm">{{ session($k) }}</div>@endif
    @endforeach
    @if ($errors->any())<div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">{{ $errors->first() }}</div>@endif

    <div class="grid sm:grid-cols-2 gap-4">
        <form method="POST" action="{{ route('admin.courier-payments.store') }}" enctype="multipart/form-data" class="{{ $card }} p-5 space-y-3">
            @csrf
            <p class="font-semibold text-textprimary dark:text-white">Match a payment file</p>
            <p class="text-xs text-textsecondary">Download the payment / invoice list from the courier panel and save it as <b>CSV</b>
                (in Excel: File → Save As → CSV). We match every line by consignment id, tracking code or invoice number.</p>
            <label class="block"><span class="text-xs text-textsecondary">Courier account</span>
                <select name="account_id" required class="{{ $input }}">
                    @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->label() }}</option>@endforeach
                </select></label>
            <label class="block"><span class="text-xs text-textsecondary">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" required class="{{ $input }}"></label>
            <label class="block"><span class="text-xs text-textsecondary">Payment reference (optional)</span>
                <input name="reference" maxlength="100" class="{{ $input }}" placeholder="e.g. Steadfast invoice 1234"></label>
            <button class="px-5 py-2 rounded-xl bg-primary text-white text-sm font-semibold" @disabled($accounts->isEmpty())>Match</button>
            @if ($accounts->isEmpty())<p class="text-xs text-red-600">Add a courier account first (Settings → Courier).</p>@endif
        </form>

        <div class="{{ $card }} p-5 space-y-2">
            <p class="font-semibold text-textprimary dark:text-white">Delivered, not paid yet</p>
            @forelse ($unpaid as $u)
                <div class="flex justify-between text-sm border-b border-border dark:border-gray-700 py-1.5">
                    <span>{{ \App\Models\CourierSetting::COURIERS[$u->courier_name]['label'] ?? ucfirst($u->courier_name) }}</span>
                    <span>{{ $u->c }} parcels · <b>৳{{ number_format($u->amount) }}</b></span>
                </div>
            @empty
                <p class="text-sm text-textsecondary">Nothing waiting.</p>
            @endforelse
        </div>
    </div>

    <div class="{{ $card }} overflow-x-auto">
        <table class="ep-stack w-full text-sm">
            <thead><tr class="text-left text-xs text-textsecondary border-b border-border dark:border-gray-700">
                <th class="p-3">Date</th><th>Courier</th><th>File</th><th class="text-right">Collected</th><th class="text-right">Charge</th>
                <th class="text-center">Matched</th><th class="text-center">Different</th><th class="text-center">Unknown</th><th></th></tr></thead>
            <tbody>
                @forelse ($payments as $p)
                    <tr class="border-b border-border dark:border-gray-700">
                        <td class="p-3 whitespace-nowrap">{{ $p->created_at->format('d M Y') }}<br><span class="text-xs text-textsecondary">{{ $p->uploader?->name }}</span></td>
                        <td>{{ \App\Models\CourierSetting::COURIERS[$p->courier]['label'] ?? $p->courier }}</td>
                        <td class="max-w-[200px] truncate">{{ $p->reference ?: $p->file_name }}</td>
                        <td class="text-right">৳{{ number_format($p->total_collected) }}</td>
                        <td class="text-right">৳{{ number_format($p->total_charge) }}</td>
                        <td class="text-center text-green-700">{{ $p->matched }}</td>
                        <td class="text-center {{ $p->mismatched ? 'text-red-600 font-bold' : '' }}">{{ $p->mismatched }}</td>
                        <td class="text-center {{ $p->unknown ? 'text-amber-600' : '' }}">{{ $p->unknown }}</td>
                        <td class="pr-3"><a href="{{ route('admin.courier-payments.show', $p) }}" class="text-primary text-xs">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-6 text-center text-textsecondary">No payment file matched yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
