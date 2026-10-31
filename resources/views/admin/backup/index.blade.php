@extends('layouts.admin')

@section('title', 'Backup')
@section('page-title', 'Backup')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Backup</span>
@endsection

@php
    $card  = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm';
    $input = 'w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50';
    $busy  = in_array($task['state'] ?? null, ['waiting', 'running'], true);
    $last  = ($status['at'] ?? null) ? \Carbon\Carbon::parse($status['at']) : null;
    $stale = $last && $last->lt(now()->subHours(36));
    $mb    = fn ($b) => number_format($b / 1048576, $b < 10485760 ? 2 : 1) . ' MB';
@endphp

@section('content')
<div class="max-w-4xl space-y-5">
    @foreach (['success' => 'green', 'error' => 'red'] as $k => $c)
        @if (session($k))<div class="p-3 rounded-xl bg-{{ $c }}-50 text-{{ $c }}-800 border border-{{ $c }}-200 text-sm">{{ session($k) }}</div>@endif
    @endforeach
    @if ($errors->any())<div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">{{ $errors->first() }}</div>@endif

    @if (! $connected)
        {{-- Not connected yet --}}
        <div class="{{ $card }} p-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/></svg>
            </div>
            <p class="text-lg font-bold text-textprimary dark:text-white">Keep a copy of your data in your own Google Drive</p>
            <p class="text-sm text-textsecondary mt-1 max-w-xl mx-auto">Every day your orders, products & stock, customers and courier parcels are saved to your Google Drive automatically.
                If anything is ever lost, you bring it back here with one click.</p>
            @if ($available)
                <a href="{{ route('admin.backup.connect') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-3 rounded-xl bg-primary text-white text-sm font-semibold">Connect Google Drive</a>
                <p class="text-xs text-textsecondary mt-3">Sign in with your Google account and press <b>Allow</b>. The CRM can only see the backup files it makes — nothing else in your Drive.</p>
            @else
                <p class="mt-4 text-sm text-amber-700">Google Drive backup is not switched on for this CRM yet. Please contact support.</p>
            @endif
        </div>
    @else
        {{-- Health --}}
        @php
            $tone = ! $last ? ['bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-700', 'text-gray-700 dark:text-gray-200', 'No backup yet']
                : (($status['ok'] ?? false) === false ? ['bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800', 'text-red-700 dark:text-red-400', 'Last backup failed']
                : ($stale ? ['bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800', 'text-amber-700 dark:text-amber-400', 'Last backup is old']
                : ['bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800', 'text-green-700 dark:text-green-400', 'Your data is backed up']));
        @endphp
        <div class="rounded-2xl border p-5 {{ $tone[0] }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-lg font-bold {{ $tone[1] }}">{{ $tone[2] }}</p>
                    @if ($last)
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">Last: {{ $last->format('d M Y, h:i A') }} ({{ $last->diffForHumans() }})
                            @if (isset($status['orders'])) · {{ number_format($status['orders']) }} orders @endif
                            @if (! empty($status['size'])) · {{ $mb($status['size']) }} @endif</p>
                    @endif
                    @if (! empty($status['message']))<p class="text-sm mt-1 {{ ($status['ok'] ?? true) ? 'text-gray-600 dark:text-gray-400' : 'text-red-700 font-medium' }}">{{ $status['message'] }}</p>@endif
                    <p class="text-xs text-textsecondary mt-2">Saved by itself every night to <b>{{ $folder }}</b> in {{ $email ?: 'your Google Drive' }}.</p>
                </div>
                <form method="POST" action="{{ route('admin.backup.now') }}">@csrf
                    <button @disabled($busy) class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold disabled:opacity-50">Backup now</button>
                </form>
            </div>

            @if ($task)
                <div class="mt-4 p-3 rounded-xl text-sm {{ ($task['state'] ?? '') === 'failed' ? 'bg-red-100 text-red-800' : 'bg-white/70 dark:bg-gray-900/40 text-gray-800 dark:text-gray-200' }}">
                    @if (($task['state'] ?? '') === 'failed')
                        <b>{{ $task['action'] === 'restore' ? 'Restore' : 'Backup' }} failed:</b> {{ $task['error'] ?? '' }}
                        <form method="POST" action="{{ route('admin.backup.cancel') }}" class="inline">@csrf <button class="ml-2 underline">Dismiss</button></form>
                    @else
                        <span class="inline-block w-3 h-3 mr-1 rounded-full border-2 border-blue-600 border-t-transparent animate-spin align-middle"></span>
                        <b>{{ $task['action'] === 'restore' ? 'Bringing back your data' : 'Backing up' }}…</b>
                        {{ ($task['state'] ?? '') === 'waiting' ? 'starts within a minute.' : 'running now.' }} This page updates by itself.
                    @endif
                </div>
            @endif
        </div>

        {{-- Readable copy --}}
        @if ($csv && $csv['link'])
            <div class="{{ $card }} p-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-textprimary dark:text-white">Orders list (Excel / Google Sheets)</p>
                    <p class="text-xs text-textsecondary">{{ $csv['name'] }} — open it in your Drive to read all your orders.</p>
                </div>
                <a href="{{ $csv['link'] }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl border border-border dark:border-gray-600 text-sm font-semibold">Open in Drive</a>
            </div>
        @endif

        {{-- Backups --}}
        <div class="{{ $card }}">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700">
                <p class="font-semibold text-textprimary dark:text-white">Your backups</p>
                <p class="text-xs text-textsecondary">Restore brings your shop back to that day: orders, products & stock, customers, courier parcels.
                    Your wallet, staff logins and courier/website keys stay as they are. Your current data is saved first, just in case.</p>
            </div>
            @if ($driveError)<p class="p-5 text-sm text-red-600">{{ $driveError }}</p>@endif
            <div class="divide-y divide-border dark:divide-gray-700">
                @forelse ($files as $f)
                    <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-textprimary dark:text-white">{{ $f['created'] ? \Carbon\Carbon::parse($f['created'])->timezone(config('app.timezone'))->format('d M Y, h:i A') : $f['name'] }}
                                @if ($loop->first)<span class="ml-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold">Latest</span>@endif</p>
                            <p class="text-xs text-textsecondary">{{ $mb($f['size']) }}</p>
                        </div>
                        <details class="text-right">
                            <summary class="inline-block cursor-pointer list-none px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 text-xs font-semibold hover:bg-amber-100">Restore…</summary>
                            <form method="POST" action="{{ route('admin.backup.restore') }}" class="mt-2 flex flex-wrap justify-end gap-2"
                                  onsubmit="return confirm('Bring your shop back to this backup? Changes made after it are replaced.')">@csrf
                                <input type="hidden" name="file_id" value="{{ $f['id'] }}">
                                <input type="hidden" name="name" value="{{ $f['name'] }}">
                                <input name="confirm" required placeholder="Type RESTORE" class="w-36 {{ $input }} !py-1.5 text-xs">
                                <button @disabled($busy) class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold disabled:opacity-50">Restore this</button>
                            </form>
                        </details>
                    </div>
                @empty
                    <p class="p-5 text-sm text-textsecondary">No backups yet — press "Backup now".</p>
                @endforelse
            </div>
        </div>

        {{-- Settings --}}
        <div class="{{ $card }} p-5 flex flex-wrap items-end justify-between gap-4">
            <form method="POST" action="{{ route('admin.backup.settings') }}" class="flex items-end gap-2">@csrf
                <label class="block text-xs text-textsecondary">Keep the last
                    <select name="keep" class="{{ $input }} mt-1">
                        @foreach (\App\Services\Backup\BackupManager::KEEP_OPTIONS as $k)<option value="{{ $k }}" @selected($keep === $k)>{{ $k }} days</option>@endforeach
                    </select></label>
                <button class="px-4 py-2 rounded-xl border border-border dark:border-gray-600 text-sm font-semibold">Save</button>
            </form>
            <form method="POST" action="{{ route('admin.backup.disconnect') }}" onsubmit="return confirm('Stop backing up to this Google Drive?')">@csrf
                <button class="px-4 py-2 rounded-xl bg-red-50 text-red-700 text-sm font-semibold">Disconnect Google Drive</button>
            </form>
        </div>
    @endif
</div>

@if ($busy)
<script>
(function poll() {
    setTimeout(() => fetch('{{ route('admin.backup.poll') }}', { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(d => { const s = d.task && d.task.state; (s === 'waiting' || s === 'running') ? poll() : location.reload(); })
        .catch(poll), 8000);
})();
</script>
@endif
@endsection
