@extends('layouts.admin')

@section('title', 'Google Sheet — ' . $site->name)
@section('page-title', 'Google Sheet: ' . $site->name)

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.integrations.wordpress.index') }}" class="text-textsecondary hover:text-textprimary">Integrations</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Google Sheet</span>
@endsection

@php
    $card  = 'p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm';
    $mapping = old('mapping', $settings['mapping'] ?? []);
    $last = $settings['last_result'] ?? null;
@endphp

@section('content')
<div class="max-w-4xl space-y-5"
     x-data="sheetSetup(@js($settings['sheet_url'] ?? ''), @js(array_values(array_unique(array_filter(array_values($mapping))))), @js((object) $mapping))">

    @if (session('success'))
        <div class="p-3 rounded-xl bg-green-50 text-green-800 border border-green-200 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">{{ session('error') }}</div>
    @endif

    {{-- How to --}}
    <div class="{{ $card }} text-sm text-textsecondary space-y-2">
        <p class="font-semibold text-textprimary dark:text-white">How it works</p>
        <ol class="list-decimal ml-5 space-y-1">
            <li>Open your Google Sheet → <b>Share</b> → General access: <b>Anyone with the link</b> (Viewer).</li>
            <li>Paste the sheet link below and press <b>Read columns</b>.</li>
            <li>Match your columns with the CRM fields (we guess most of them), then save.</li>
            <li>Press <b>Import now</b>. With auto-import on, new rows come in every 5 minutes. A row is never imported twice.</li>
        </ol>
        <p class="text-xs">Rows with the same <i>Order ID</i> become one order with several products. Rows without a valid Bangladeshi mobile number, name or product are ignored.</p>
    </div>

    <form method="POST" action="{{ route('admin.integrations.sheet.update', $site) }}" class="{{ $card }} space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Google Sheet link</label>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="url" name="sheet_url" x-model="url" required class="{{ $input }}"
                       placeholder="https://docs.google.com/spreadsheets/d/…/edit#gid=0">
                <button type="button" @click="readColumns()" :disabled="loading || !url"
                        class="px-4 py-2 rounded-lg bg-gray-900 dark:bg-gray-700 text-white text-sm font-medium whitespace-nowrap disabled:opacity-50">
                    <span x-text="loading ? 'Reading…' : 'Read columns'"></span>
                </button>
            </div>
            <p class="text-xs text-textsecondary mt-1">Using a tab other than the first? Open that tab and copy the link — it ends with <code>#gid=…</code>.</p>
            <p x-show="error" x-text="error" class="text-sm text-red-600 mt-2"></p>
            <p x-show="rowCount !== null" class="text-sm text-green-700 mt-2">Found <b x-text="headers.length"></b> columns and <b x-text="rowCount"></b> rows.</p>
        </div>

        <div x-show="headers.length" x-cloak>
            <p class="text-sm font-medium mb-2">Match columns</p>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach ($fields as $key => $label)
                    <label class="block">
                        <span class="text-xs text-textsecondary">{{ $label }}</span>
                        <select name="mapping[{{ $key }}]" x-model="map['{{ $key }}']" class="{{ $input }}"
                                @if (in_array($key, $required)) required @endif>
                            <option value="">— not in my sheet —</option>
                            <template x-for="h in headers" :key="h">
                                <option :value="h" x-text="h" :selected="map['{{ $key }}'] === h"></option>
                            </template>
                        </select>
                    </label>
                @endforeach
            </div>
        </div>

        <div x-show="sample.length" x-cloak class="overflow-x-auto">
            <p class="text-sm font-medium mb-2">First rows (preview)</p>
            <table class="min-w-full text-xs border border-gray-200 dark:border-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr><template x-for="h in headers" :key="h"><th class="px-2 py-1 text-left font-medium" x-text="h"></th></template></tr>
                </thead>
                <tbody>
                    <template x-for="(r, i) in sample" :key="i">
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <template x-for="h in headers" :key="h"><td class="px-2 py-1 whitespace-nowrap" x-text="r[h] ?? ''"></td></template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <label class="block">
                <span class="text-sm font-medium">Start from sheet row</span>
                <input type="number" name="start_row" min="2" value="{{ old('start_row', $settings['start_row'] ?? 2) }}" class="{{ $input }} mt-1">
                <span class="text-xs text-textsecondary">Row 1 is the header. Use a higher number to skip old orders.</span>
            </label>
            <label class="flex items-start gap-2 mt-6">
                <input type="hidden" name="auto_import" value="0">
                <input type="checkbox" name="auto_import" value="1" class="mt-1 rounded" @checked(old('auto_import', $settings['auto_import'] ?? true))>
                <span class="text-sm">Import new rows automatically every 5 minutes</span>
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="!headers.length"
                    class="px-5 py-2 rounded-lg bg-primary text-white text-sm font-semibold disabled:opacity-50">Save settings</button>
        </div>
    </form>

    {{-- Import --}}
    <div class="{{ $card }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="text-sm">
            <p class="font-semibold text-textprimary dark:text-white">Import</p>
            @if ($last)
                <p class="text-textsecondary">
                    Last run {{ \Illuminate\Support\Carbon::parse($last['at'])->diffForHumans() }}:
                    @if ($last['error'])
                        <span class="text-red-600">{{ $last['error'] }}</span>
                    @else
                        {{ $last['created'] }} new, {{ $last['updated'] }} updated, {{ $last['invalid'] }} ignored
                    @endif
                </p>
            @else
                <p class="text-textsecondary">Not imported yet.</p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.integrations.sheet.import', $site) }}">
            @csrf
            <button type="submit" @disabled(empty($settings['mapping']))
                    class="px-5 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold disabled:opacity-50">Import now</button>
        </form>
    </div>

    @if ($logs->count())
        <div class="{{ $card }}">
            <p class="font-semibold text-sm mb-2">History</p>
            <ul class="text-xs space-y-1">
                @foreach ($logs as $log)
                    <li class="flex gap-2">
                        <span class="text-textsecondary w-32 shrink-0">{{ $log->created_at?->format('d M, h:i A') }}</span>
                        <span class="{{ $log->status === 'failed' ? 'text-red-600' : '' }}">{{ $log->message }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<script>
function sheetSetup(url, headers, map) {
    return {
        url, headers, map: Object.assign({}, map), sample: [], rowCount: null, loading: false, error: '',
        async readColumns() {
            this.loading = true; this.error = '';
            try {
                const res = await fetch(@js(route('admin.integrations.sheet.columns', $site)), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ sheet_url: this.url }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { this.error = data.message || 'Could not read the sheet.'; return; }
                this.headers = data.headers; this.sample = data.sample; this.rowCount = data.rows;
                for (const [k, v] of Object.entries(data.guess)) { if (!this.map[k]) this.map[k] = v; }
            } catch (e) { this.error = 'Could not read the sheet.'; }
            finally { this.loading = false; }
        },
    };
}
</script>
@endsection
