@extends('super-admin.layout')

@section('page-title', 'Backup')

@php
    $card  = 'bg-white rounded-xl shadow-sm border border-gray-100';
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400';
    $busy  = in_array($task['state'] ?? null, ['waiting', 'running'], true);
    $last  = $status['at'] ?? null ? \Carbon\Carbon::parse($status['at']) : null;
    $stale = $last && $last->lt(now()->subHours(36));
    $mb    = fn ($b) => number_format($b / 1048576, $b < 10485760 ? 2 : 1) . ' MB';
@endphp

@section('content')
<div class="space-y-5 max-w-5xl">
    @if ($errors->any())<div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Health --}}
    @php
        $tone = ! $last ? ['bg-gray-50 border-gray-200', 'text-gray-700', 'No backup yet']
            : (($status['ok'] ?? false) === false ? ['bg-red-50 border-red-200', 'text-red-700', 'Last backup FAILED']
            : (! ($status['drive'] ?? false) || $stale ? ['bg-amber-50 border-amber-200', 'text-amber-700', $stale ? 'Last backup is old' : 'Backup is only on this server']
            : ['bg-green-50 border-green-200', 'text-green-700', 'Backups are working']));
    @endphp
    <div class="rounded-xl border p-5 {{ $tone[0] }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-lg font-bold {{ $tone[1] }}">{{ $tone[2] }}</p>
                @if ($last)
                    <p class="text-sm text-gray-700 mt-0.5">Last: {{ $last->format('d M Y, h:i A') }} ({{ $last->diffForHumans() }})
                        @if (! empty($status['size'])) · {{ $mb($status['size']) }} · {{ number_format($status['rows'] ?? 0) }} rows · {{ number_format($status['files'] ?? 0) }} files @endif</p>
                @endif
                @if (! empty($status['message']))<p class="text-sm {{ ($status['ok'] ?? true) ? 'text-gray-600' : 'text-red-700 font-medium' }} mt-1">{{ $status['message'] }}</p>@endif
                <p class="text-xs text-gray-500 mt-2">Runs by itself every day at 2:30 AM. Keeps the last {{ $keep }} backups in Google Drive and {{ \App\Services\Backup\BackupManager::LOCAL_KEEP }} on the server.</p>
            </div>
            <form method="POST" action="{{ route('super.backup.now') }}">@csrf
                <button @disabled($busy) class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50">Backup now</button>
            </form>
        </div>

        @if ($task)
            <div id="taskBox" class="mt-4 p-3 rounded-lg text-sm {{ ($task['state'] ?? '') === 'failed' ? 'bg-red-100 text-red-800' : 'bg-white/70 text-gray-800' }}">
                @if (($task['state'] ?? '') === 'failed')
                    <b>{{ $task['action'] === 'restore' ? 'Restore' : 'Backup' }} failed:</b> {{ $task['error'] ?? '' }}
                    <form method="POST" action="{{ route('super.backup.cancel') }}" class="inline">@csrf <button class="ml-2 underline">Dismiss</button></form>
                @else
                    <span class="inline-block w-3 h-3 mr-1 rounded-full border-2 border-blue-600 border-t-transparent animate-spin align-middle"></span>
                    <b>{{ $task['action'] === 'restore' ? 'Restoring ' . ($task['name'] ?? '') : 'Backing up' }}…</b>
                    {{ ($task['state'] ?? '') === 'waiting' ? 'starts within a minute.' : 'running now — this can take a few minutes.' }} This page updates by itself.
                    @if (($task['state'] ?? '') === 'waiting')
                        <form method="POST" action="{{ route('super.backup.cancel') }}" class="inline">@csrf <button class="ml-2 underline text-gray-600">Cancel</button></form>
                    @endif
                @endif
            </div>
        @endif
    </div>

    {{-- Google Drive --}}
    <div class="{{ $card }}">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <p class="font-semibold">Google Drive</p>
            @if ($connected)
                <span class="px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Connected{{ $email ? ': ' . $email : '' }}</span>
            @else
                <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">Not connected</span>
            @endif
        </div>
        <div class="p-5 grid lg:grid-cols-2 gap-6">
            <form method="POST" action="{{ route('super.backup.client') }}" class="space-y-3">@csrf
                <p class="text-sm font-semibold text-gray-800">1. Google app</p>
                <p class="text-xs text-gray-500">From Google Cloud → Google Auth Platform → Clients → <b>Web application</b>. Add this as an <b>Authorized redirect URI</b>:</p>
                <div class="flex gap-2">
                    <input readonly value="{{ $redirect }}" class="{{ $input }} font-mono text-xs bg-gray-50" id="redirectUri">
                    <button type="button" class="px-3 rounded-lg bg-gray-100 text-xs font-semibold hover:bg-gray-200"
                            onclick="navigator.clipboard.writeText(document.getElementById('redirectUri').value); this.textContent='Copied'">Copy</button>
                </div>
                <label class="block text-xs text-gray-500">Client ID
                    <input name="client_id" value="{{ old('client_id', $client['client_id'] ?? '') }}" required class="{{ $input }} mt-1 font-mono text-xs" placeholder="xxxx.apps.googleusercontent.com"></label>
                <label class="block text-xs text-gray-500">Client secret {{ $client ? '(saved — leave empty to keep)' : '' }}
                    <input name="client_secret" type="password" autocomplete="off" class="{{ $input }} mt-1 font-mono text-xs" placeholder="{{ $client ? '••••••••' : 'GOCSPX-…' }}"></label>
                <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold hover:bg-gray-900">Save</button>
            </form>

            <div class="space-y-3">
                <p class="text-sm font-semibold text-gray-800">2. Connect</p>
                @if ($connected)
                    <p class="text-sm text-gray-600">Backups go to the folder <b>{{ \App\Services\Backup\GoogleDrive::for(null)->folderName() }}</b> in {{ $email ?: 'your Drive' }}. The CRM can only see the files it put there.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('super.backup.connect') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-sm font-semibold hover:bg-gray-200">Reconnect</a>
                        <form method="POST" action="{{ route('super.backup.disconnect') }}" onsubmit="return confirm('Stop sending backups to this Google Drive?')">@csrf
                            <button class="px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-semibold hover:bg-red-100">Disconnect</button></form>
                    </div>
                @elseif ($client)
                    <p class="text-sm text-gray-600">Sign in with the Google account whose Drive should keep the backups and press <b>Allow</b>.
                        If Google says the app is not verified: <b>Advanced → Go to … → Continue</b>.</p>
                    <a href="{{ route('super.backup.connect') }}" class="inline-block px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Connect Google Drive</a>
                @else
                    <p class="text-sm text-gray-500">Save the Google app first (step 1).</p>
                @endif

                <form method="POST" action="{{ route('super.backup.settings') }}" class="flex items-end gap-2 pt-3 border-t border-gray-100">@csrf
                    <label class="block text-xs text-gray-500 flex-1">Keep the last
                        <select name="keep" class="{{ $input }} mt-1">
                            @foreach (\App\Services\Backup\BackupManager::KEEP_OPTIONS as $k)<option value="{{ $k }}" @selected($keep === $k)>{{ $k }} backups ({{ $k }} days)</option>@endforeach
                        </select></label>
                    <button class="px-4 py-2 rounded-lg bg-gray-100 text-sm font-semibold hover:bg-gray-200">Save</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Backups in Drive --}}
    <div class="{{ $card }}">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="font-semibold">Backups in Google Drive</p>
            <p class="text-xs text-gray-500">Restore puts ALL shops' data back to that day. Everything done after it is replaced — the current data is saved on the server first, just in case.</p>
        </div>
        @if ($driveError)<p class="p-5 text-sm text-red-600">{{ $driveError }}</p>@endif
        <div class="divide-y divide-gray-100">
            @forelse ($files as $f)
                <div class="px-5 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $f['created'] ? \Carbon\Carbon::parse($f['created'])->timezone(config('app.timezone'))->format('d M Y, h:i A') : $f['name'] }}
                                @if ($loop->first)<span class="ml-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold">Latest</span>@endif</p>
                            <p class="text-xs text-gray-500 font-mono truncate">{{ $f['name'] }} · {{ $mb($f['size']) }}</p>
                        </div>
                        <details class="text-right">
                            <summary class="inline-block cursor-pointer list-none px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 text-xs font-semibold hover:bg-amber-100">Restore…</summary>
                            <form method="POST" action="{{ route('super.backup.restore') }}" class="mt-2 flex flex-wrap justify-end gap-2"
                                  onsubmit="return confirm('Replace ALL current data with this backup?')">@csrf
                                <input type="hidden" name="file_id" value="{{ $f['id'] }}">
                                <input type="hidden" name="name" value="{{ $f['name'] }}">
                                <input name="confirm" required placeholder="Type RESTORE" class="w-36 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                                <button @disabled($busy) class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 disabled:opacity-50">Restore this</button>
                            </form>
                        </details>
                    </div>
                </div>
            @empty
                <p class="p-5 text-sm text-gray-500">{{ $connected ? 'No backups in Drive yet — press "Backup now".' : 'Connect Google Drive to see backups here.' }}</p>
            @endforelse
        </div>
    </div>

    {{-- Disaster note --}}
    <div class="{{ $card }} p-5 text-sm text-gray-700 space-y-2">
        <p class="font-semibold">If the server is ever lost</p>
        <ol class="list-decimal list-inside space-y-1 text-gray-600">
            <li>Set up a new server and deploy the code from GitHub (as now), with the <b>same APP_KEY</b> in <code>.env</code>.</li>
            <li>Sign in as super admin, open Backup, save the Google app and connect the same Drive.</li>
            <li>Press <b>Restore</b> on the latest backup. All shops, orders, stock and wallets come back.</li>
        </ol>
        <p class="text-xs text-amber-700 bg-amber-50 rounded-lg p-2">Keep a copy of the server's <b>APP_KEY</b> (in <code>.env</code>) in a safe place, e.g. a password manager. Saved courier keys and passwords for shops' integrations are locked with it; with a different key they must be entered again.</p>
    </div>
</div>

@if ($busy)
<script>
(function poll() {
    setTimeout(() => fetch('{{ route('super.backup.poll') }}', { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(d => { const s = d.task && d.task.state; (s === 'waiting' || s === 'running') ? poll() : location.reload(); })
        .catch(poll), 8000);
})();
</script>
@endif
@endsection
