@extends('layouts.admin')

@section('title', 'Packing & dispatch')
@section('page-title', 'Packing & dispatch')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Packing & dispatch</span>
@endsection

@php
    $card = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm';
@endphp

@section('content')
<div class="max-w-4xl space-y-4" x-data="dispatchDesk(@js($accounts->map(fn ($a) => ['id' => $a->id, 'label' => $a->label()])->values()))" x-init="init()">

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach (['packed' => 'Packed today', 'handed' => 'Handed over today', 'received' => 'Returns received today', 'waiting' => 'Returns to receive'] as $k => $label)
            <div class="{{ $card }} p-3">
                <p class="text-xs text-textsecondary">{{ $label }}</p>
                <p class="text-xl font-bold text-textprimary dark:text-white">{{ $today[$k] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 overflow-x-auto">
        <template x-for="t in tabs" :key="t.key">
            <button @click="switchTab(t.key)" class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap"
                    :class="tab === t.key ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 border border-border dark:border-gray-700'"
                    x-text="t.label"></button>
        </template>
    </div>

    <div class="{{ $card }} p-4 space-y-3">
        <p class="text-sm text-textsecondary" x-text="tabs.find(t => t.key === tab).help"></p>

        <template x-if="tab === 'handover'">
            <label class="block">
                <span class="text-xs text-textsecondary">Book parcels that are not booked yet with</span>
                <select x-model="accountId" class="w-full mt-1 px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50">
                    <option value="">— only parcels already booked —</option>
                    <template x-for="a in accounts" :key="a.id"><option :value="a.id" x-text="a.label"></option></template>
                </select>
            </label>
        </template>

        <form @submit.prevent="scan(code)" class="flex gap-2">
            <input x-ref="input" x-model="code" autocomplete="off" autofocus inputmode="text"
                   class="flex-1 min-w-0 px-3 sm:px-4 py-3 text-lg rounded-xl border-2 border-primary/40 focus:border-primary focus:outline-none bg-white dark:bg-gray-900"
                   placeholder="Scan or type the order number / tracking code">
            <button class="px-3 sm:px-4 rounded-xl bg-primary text-white font-semibold" :disabled="busy">Find</button>
            <button type="button" @click="toggleCamera()" class="px-3 sm:px-4 rounded-xl border border-border dark:border-gray-600 font-semibold"
                    x-text="camera ? 'Stop' : 'Camera'"></button>
        </form>
        <div x-show="camera" x-cloak id="ep-scanner" class="rounded-xl overflow-hidden max-w-md"></div>
        <p x-show="flash" x-cloak class="text-sm font-medium px-3 py-2 rounded-xl"
           :class="flashOk ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'" x-text="flash"></p>
    </div>

    {{-- The order just scanned (pack / returns) --}}
    <template x-if="current && tab !== 'handover'">
        <div class="{{ $card }} p-4 space-y-3">
            <div class="flex flex-wrap justify-between gap-2">
                <div>
                    <p class="text-lg font-bold">#<span x-text="current.number"></span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700" x-text="current.status.replace(/_/g, ' ')"></span></p>
                    <p class="text-sm"><span x-text="current.name"></span> · <span x-text="current.phone"></span></p>
                    <p class="text-xs text-textsecondary" x-text="current.address"></p>
                </div>
                <div class="text-right text-sm">
                    <p>COD <b>৳<span x-text="current.cod.toLocaleString()"></span></b></p>
                    <p x-show="current.courier" class="text-xs text-textsecondary"><span x-text="current.courier?.name"></span> · <span x-text="current.courier?.tracking"></span> · <span x-text="current.courier?.status"></span></p>
                </div>
            </div>
            <p x-show="current.note" class="text-sm p-2 rounded-lg bg-amber-50 text-amber-800">Packing note: <span x-text="current.note"></span></p>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-textsecondary"><th class="py-1">Item</th><th>Size / colour</th><th class="text-right">Qty</th></tr></thead>
                <tbody>
                    <template x-for="(i, n) in current.items" :key="n">
                        <tr class="border-t border-border dark:border-gray-700">
                            <td class="py-2" x-text="i.name"></td><td x-text="i.variant || '—'"></td>
                            <td class="text-right text-lg font-bold" x-text="i.qty"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="flex justify-end gap-2" x-show="canAct">
                <template x-if="tab === 'returns'">
                    <input x-model="note" placeholder="Note (e.g. one item missing)" class="flex-1 px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-900">
                </template>
                <button @click="act()" :disabled="busy" class="px-6 py-3 rounded-xl bg-green-600 text-white font-bold"
                        x-text="tab === 'pack' ? 'Packed ✓' : 'Received ✓'"></button>
            </div>
        </div>
    </template>

    {{-- Hand-over list --}}
    <template x-if="tab === 'handover'">
        <div class="{{ $card }} p-4 space-y-3">
            <div class="flex justify-between items-center">
                <p class="font-semibold"><span x-text="batch.length"></span> parcels scanned</p>
                <div class="flex gap-2">
                    <button x-show="batch.length" @click="batch = []; results = []" class="px-3 py-2 rounded-xl border border-border text-sm">Clear</button>
                    <button x-show="batch.length" @click="handOver()" :disabled="busy" class="px-5 py-2 rounded-xl bg-green-600 text-white font-semibold text-sm"
                            x-text="busy ? 'Working…' : 'Hand over ' + batch.length + ' parcels'"></button>
                </div>
            </div>
            <ul class="divide-y divide-border dark:divide-gray-700 text-sm">
                <template x-for="o in batch" :key="o.id">
                    <li class="py-2 flex justify-between gap-2">
                        <span><b>#<span x-text="o.number"></span></b> · <span x-text="o.name"></span> · ৳<span x-text="o.cod"></span>
                            <span class="block text-xs" :class="resultFor(o.id) ? (resultFor(o.id).ok ? 'text-green-700' : 'text-red-600') : 'text-textsecondary'"
                                  x-text="resultFor(o.id)?.message || o.hint"></span></span>
                        <button @click="batch = batch.filter(b => b.id !== o.id)" class="text-xs text-red-600">Remove</button>
                    </li>
                </template>
            </ul>
        </div>
    </template>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function dispatchDesk(accounts) {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const post = (url, body) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) })
        .then(async r => ({ status: r.status, ...(await r.json().catch(() => ({ message: 'Server error' }))) }));
    let audio;
    const beep = (ok) => { try { audio ??= new AudioContext(); const o = audio.createOscillator(), g = audio.createGain(); o.frequency.value = ok ? 880 : 220; g.gain.value = 0.15; o.connect(g); g.connect(audio.destination); o.start(); o.stop(audio.currentTime + (ok ? 0.12 : 0.4)); } catch (e) {} };

    return {
        accounts, accountId: accounts[0]?.id ?? '', tab: 'pack', code: '', note: '', busy: false,
        current: null, canAct: false, flash: '', flashOk: true, batch: [], results: [], camera: false, scanner: null, lastScan: '',
        tabs: [
            { key: 'pack', label: '1 · Pack', help: 'Scan the invoice barcode, check the items, press Packed.' },
            { key: 'handover', label: '2 · Hand over to courier', help: 'Scan every parcel the rider takes. Parcels not booked yet are booked with the chosen account.' },
            { key: 'returns', label: '3 · Receive returns', help: 'Scan a returned parcel, check it, press Received — the stock goes back.' },
        ],
        init() { this.$nextTick(() => this.$refs.input.focus()); },
        switchTab(t) { this.tab = t; this.current = null; this.flash = ''; this.$nextTick(() => this.$refs.input.focus()); },
        say(msg, ok) { this.flash = msg; this.flashOk = ok; beep(ok); },
        resultFor(id) { return this.results.find(r => r.id === id); },
        async scan(code) {
            code = (code || '').trim();
            if (!code || this.busy) return;
            this.busy = true; this.code = '';
            const r = await post(@js(route('admin.dispatch.lookup')), { code, mode: this.tab });
            this.busy = false;
            this.$refs.input.focus();
            if (!r.order) { this.current = null; return this.say(r.message || 'Not found', false); }
            if (this.tab === 'handover') {
                if (!r.ok) return this.say('#' + r.order.number + ': ' + r.message, false);
                if (this.batch.some(b => b.id === r.order.id)) return this.say('#' + r.order.number + ' is already in the list.', false);
                this.batch.unshift({ ...r.order, hint: r.message });
                return this.say('#' + r.order.number + ' added.', true);
            }
            this.current = r.order; this.canAct = r.ok; this.note = '';
            this.say(r.message, r.ok);
        },
        async act() {
            this.busy = true;
            const url = this.tab === 'pack' ? @js(route('admin.dispatch.pack')) : @js(route('admin.dispatch.receive'));
            const r = await post(url, { order_id: this.current.id, note: this.note });
            this.busy = false;
            this.say(r.message, !!r.ok);
            if (r.ok) { this.current = r.order; this.canAct = false; }
            this.$refs.input.focus();
        },
        async handOver() {
            this.busy = true;
            const r = await post(@js(route('admin.dispatch.handover')), { account_id: this.accountId || null, order_ids: this.batch.map(b => b.id) });
            this.busy = false;
            this.results = r.results || [];
            this.say((r.done ?? 0) + ' of ' + this.batch.length + ' parcels handed over.' + (r.done < this.batch.length ? ' See the red lines.' : ''), r.done === this.batch.length);
            const failed = new Set(this.results.filter(x => !x.ok).map(x => x.id));
            setTimeout(() => { this.batch = this.batch.filter(b => failed.has(b.id)); }, 4000);
        },
        async toggleCamera() {
            if (this.camera) { await this.scanner?.stop().catch(() => {}); this.camera = false; return; }
            if (!window.Html5Qrcode) return this.say('Camera scanner could not load.', false);
            this.camera = true;
            await this.$nextTick();
            this.scanner = new Html5Qrcode('ep-scanner', { formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.EAN_13] });
            this.scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 280, height: 120 } }, (text) => {
                if (text === this.lastScan) return;   // the same label held in front of the camera
                this.lastScan = text; setTimeout(() => { if (this.lastScan === text) this.lastScan = ''; }, 3000);
                this.scan(text);
            }).catch(e => { this.camera = false; this.say('Camera not available: ' + e, false); });
        },
    };
}
</script>
@endsection
