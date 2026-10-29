@extends('layouts.admin')

@section('title', 'Courier accounts')
@section('page-title', 'Courier accounts')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Courier</span>
@endsection

@php
    $card  = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm';
    $input = 'w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30';
    $hooks = [
        'steadfast' => 'Steadfast panel → API → Webhook: paste the URL, and the secret as "Auth Token (Bearer)".',
        'pathao'    => 'Pathao merchant → Developer API → Webhook: paste the URL, and the secret as the webhook secret.',
        'redx'      => 'Send the URL to your RedX account manager (or add it in the RedX panel) as the parcel status callback.',
        'carrybee'  => 'CarryBee panel → Developers → Webhook: paste the URL.',
        'paperfly'  => 'Paperfly dashboard → Webhook: paste the URL, and the secret as the webhook secret.',
    ];
    $editData = $accounts->mapWithKeys(fn ($a) => [$a->id => [
        'id' => $a->id, 'courier' => $a->courier, 'name' => $a->name, 'is_active' => $a->is_active, 'sandbox' => $a->sandbox,
        'weight' => $a->extra('weight_kg', 0.5),
        'values' => collect(array_keys($couriers[$a->courier]['fields'] ?? []))
            ->reject(fn ($f) => in_array($f, \App\Models\CourierSetting::SECRET_FIELDS, true))
            ->mapWithKeys(fn ($f) => [$f => str_starts_with($f, 'extra.') ? (string) $a->extra(substr($f, 6), '') : (string) $a->{$f}])->all(),
        'secrets' => collect(\App\Models\CourierSetting::SECRET_FIELDS)
            ->filter(fn ($f) => isset($couriers[$a->courier]['fields'][$f]))
            ->mapWithKeys(fn ($f) => [$f => str_starts_with($f, 'extra.') ? filled($a->extra(substr($f, 6))) : filled($a->{$f})])->all(),
        'action' => route('admin.courier.update', $a),
    ]]);
@endphp

@section('content')
<div class="max-w-5xl space-y-5" x-data="courierAccounts(@js($couriers), @js($editData))">

    @foreach (['success' => 'green', 'error' => 'red'] as $k => $c)
        @if (session($k))
            <div class="p-3 rounded-xl bg-{{ $c }}-50 text-{{ $c }}-800 border border-{{ $c }}-200 text-sm">{{ session($k) }}</div>
        @endif
    @endforeach

    <div class="{{ $card }} p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="text-sm text-textsecondary">
            <p class="font-semibold text-textprimary dark:text-white">Your courier accounts</p>
            <p>Add one or more accounts per courier (e.g. two Pathao stores). The <b>default</b> account is used when staff don't pick one.
               Parcel status updates come in by themselves through each account's webhook, and are also checked every few hours.</p>
        </div>
        <button @click="openAdd()" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold whitespace-nowrap">+ Add account</button>
    </div>

    @forelse ($accounts->groupBy('courier') as $courier => $list)
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-textprimary dark:text-white flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg {{ $couriers[$courier]['color'] ?? 'bg-gray-500' }} text-white text-[10px] font-bold flex items-center justify-center">{{ substr($couriers[$courier]['label'] ?? $courier, 0, 2) }}</span>
                {{ $couriers[$courier]['label'] ?? ucfirst($courier) }}
            </h3>
            @foreach ($list as $a)
                <div class="{{ $card }} p-4 space-y-3" x-data="{ hook: false, test: null, testing: false }">
                    <div class="flex flex-wrap items-center gap-2 justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-textprimary dark:text-white">{{ $a->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $a->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $a->is_active ? 'On' : 'Off' }}</span>
                            @if ($a->is_default)<span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Default</span>@endif
                            @if ($a->sandbox)<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Test mode</span>@endif
                            <span class="text-xs text-textsecondary">{{ $counts[$a->id] ?? 0 }} parcels
                                · last update {{ $a->last_webhook_at ? $a->last_webhook_at->diffForHumans() : 'never' }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button class="px-3 py-1.5 rounded-lg border border-border dark:border-gray-600" :disabled="testing"
                                    @click="testing = true; test = null; fetch('{{ route('admin.courier.test', $a) }}', {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}}).then(r => r.json()).then(d => test = d).catch(() => test = {success: false, message: 'Could not reach the server.'}).finally(() => testing = false)">
                                <span x-text="testing ? 'Testing…' : 'Test'"></span></button>
                            <button class="px-3 py-1.5 rounded-lg border border-border dark:border-gray-600" @click="hook = !hook">Webhook</button>
                            <button class="px-3 py-1.5 rounded-lg border border-border dark:border-gray-600" @click="openEdit({{ $a->id }})">Edit</button>
                            @unless ($a->is_default)
                                <form method="POST" action="{{ route('admin.courier.default', $a) }}">@csrf
                                    <button class="px-3 py-1.5 rounded-lg border border-border dark:border-gray-600">Make default</button></form>
                            @endunless
                            <form method="POST" action="{{ route('admin.courier.destroy', $a) }}" onsubmit="return confirm('Delete {{ addslashes($a->label()) }}?')">@csrf @method('DELETE')
                                <button class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600">Delete</button></form>
                        </div>
                    </div>
                    <p x-show="test" x-cloak class="text-sm" :class="test?.success ? 'text-green-700' : 'text-red-600'" x-text="test?.message"></p>
                    <div x-show="hook" x-cloak class="text-xs space-y-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-900">
                        <p class="text-textsecondary">{{ $hooks[$courier] ?? 'Give this URL to the courier as the status webhook.' }}</p>
                        <div class="flex gap-2 items-center"><span class="w-16 shrink-0 font-medium">URL</span>
                            <code class="flex-1 break-all">{{ $a->webhookUrl() }}</code>
                            <button class="px-2 py-1 rounded border" @click="navigator.clipboard.writeText(@js($a->webhookUrl())); $el.textContent = 'Copied'">Copy</button></div>
                        <div class="flex gap-2 items-center"><span class="w-16 shrink-0 font-medium">Secret</span>
                            <code class="flex-1 break-all">{{ $a->webhook_token }}</code>
                            <button class="px-2 py-1 rounded border" @click="navigator.clipboard.writeText(@js($a->webhook_token)); $el.textContent = 'Copied'">Copy</button></div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="{{ $card }} p-8 text-center text-sm text-textsecondary">No courier account yet. Press <b>+ Add account</b>.</div>
    @endforelse

    {{-- Add / edit form --}}
    <div x-show="form" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="form = null">
        <form method="POST" :action="form?.action" class="{{ $card }} w-full max-w-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto" @click.outside="form = null">
            @csrf
            <template x-if="form?.id"><input type="hidden" name="_method" value="PUT"></template>
            <h3 class="font-bold text-textprimary dark:text-white" x-text="form?.id ? 'Edit account' : 'Add courier account'"></h3>

            <label class="block" x-show="!form?.id">
                <span class="text-xs text-textsecondary">Courier</span>
                <select name="courier" x-model="form.courier" class="{{ $input }}">
                    <template x-for="(c, key) in couriers" :key="key"><option :value="key" x-text="c.label"></option></template>
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-textsecondary">Account name (e.g. "Dhaka store")</span>
                <input name="name" x-model="form.name" maxlength="100" class="{{ $input }}">
            </label>

            <template x-for="(label, field) in (couriers[form?.courier]?.fields || {})" :key="form?.courier + field">
                <label class="block">
                    <span class="text-xs text-textsecondary" x-text="label"></span>
                    <input :name="fieldName(field)" :type="isSecret(field) ? 'password' : 'text'" autocomplete="off"
                           :value="form.values?.[field] ?? ''" class="{{ $input }}"
                           :placeholder="isSecret(field) && form.secrets?.[field] ? 'Saved — leave empty to keep' : ''">
                </label>
            </template>

            <label class="block">
                <span class="text-xs text-textsecondary">Default parcel weight (kg)</span>
                <input type="number" step="0.1" min="0.1" name="extra[weight_kg]" x-model="form.weight" class="{{ $input }}">
            </label>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Turned on</label>
                <label class="flex items-center gap-2"><input type="hidden" name="sandbox" value="0">
                    <input type="checkbox" name="sandbox" value="1" x-model="form.sandbox" class="rounded"> Test mode (courier sandbox)</label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="form = null" class="px-4 py-2 rounded-xl border border-border text-sm">Close</button>
                <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function courierAccounts(couriers, accounts) {
    const secrets = @js(\App\Models\CourierSetting::SECRET_FIELDS);
    return {
        couriers, accounts, form: null,
        openAdd() { this.form = { courier: 'steadfast', name: '', is_active: true, sandbox: false, weight: 0.5, values: {}, secrets: {}, action: @js(route('admin.courier.store')) }; },
        openEdit(id) { this.form = JSON.parse(JSON.stringify(this.accounts[id])); },
        isSecret(f) { return secrets.includes(f); },
        fieldName(f) { return f.startsWith('extra.') ? 'extra[' + f.slice(6) + ']' : f; },
    };
}
</script>
@endsection
