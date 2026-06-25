@extends('super-admin.layout')
@section('page-title', 'System Health')

@section('content')
@php
    $diskUsed  = $data['disk_total'] - $data['disk_free'];
    $diskPct   = $data['disk_total'] > 0 ? round(($diskUsed / $data['disk_total']) * 100) : 0;
    $memMb     = round($data['memory_usage'] / 1024 / 1024, 1);
    $memPeakMb = round($data['memory_peak'] / 1024 / 1024, 1);
    $uptime    = null;
    if ($data['uptime']) {
        $secs   = (int) explode(' ', $data['uptime'])[0];
        $days   = floor($secs / 86400);
        $hours  = floor(($secs % 86400) / 3600);
        $uptime = "{$days}d {$hours}h";
    }
@endphp

{{-- Stats Grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Failed Jobs</p>
        <p class="text-3xl font-bold mt-1 {{ $data['failed_jobs'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $data['failed_jobs'] }}</p>
        @if($data['failed_jobs'] > 0)
        <form method="POST" action="{{ route('super.system-health.clear-failed') }}" class="mt-2">
            @csrf
            <button type="submit" onclick="return confirm('Clear all failed jobs?')"
                    class="text-xs px-2 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition">Clear All</button>
        </form>
        @endif
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending Jobs</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $data['pending_jobs'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Orders</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($data['total_orders']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Users</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($data['total_users']) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Server Info --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Server Info</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">PHP Version</dt>
                <dd class="font-medium text-gray-900">{{ $data['php_version'] }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Laravel Version</dt>
                <dd class="font-medium text-gray-900">{{ $data['laravel_version'] }}</dd>
            </div>
            @if($uptime)
            <div class="flex justify-between">
                <dt class="text-gray-500">Server Uptime</dt>
                <dd class="font-medium text-green-600">{{ $uptime }}</dd>
            </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">Memory Usage</dt>
                <dd class="font-medium text-gray-900">{{ $memMb }} MB (peak: {{ $memPeakMb }} MB)</dd>
            </div>
            <div>
                <div class="flex justify-between mb-1">
                    <dt class="text-gray-500">Disk Usage</dt>
                    <dd class="font-medium {{ $diskPct >= 90 ? 'text-red-600' : ($diskPct >= 70 ? 'text-yellow-600' : 'text-gray-900') }}">
                        {{ round($diskUsed / 1024 / 1024 / 1024, 1) }} GB / {{ round($data['disk_total'] / 1024 / 1024 / 1024, 1) }} GB ({{ $diskPct }}%)
                    </dd>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $diskPct >= 90 ? 'bg-red-500' : ($diskPct >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($diskPct, 100) }}%"></div>
                </div>
            </div>
        </dl>
    </div>

    {{-- Failed Jobs List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Recent Failed Jobs</h3>
            @if($data['failed_jobs'] > 0)
            <form method="POST" action="{{ route('super.system-health.clear-failed') }}">
                @csrf
                <button type="submit" onclick="return confirm('Clear all failed jobs?')"
                        class="text-xs px-3 py-1 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">Clear All</button>
            </form>
            @endif
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentFailed as $job)
            <div class="px-6 py-3">
                <p class="text-xs font-medium text-gray-700 truncate">{{ $job->uuid }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($job->failed_at)->format('d M Y H:i') }}</p>
                <p class="text-xs text-red-500 mt-0.5 truncate">{{ Str::limit(json_decode($job->exception ?? '""'), 80) }}</p>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No failed jobs.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Log Lines --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Recent Log Entries</h3>
        <p class="text-xs text-gray-400 mt-0.5">Last 50 lines from storage/logs/laravel.log</p>
    </div>
    <div class="p-4">
        @if($recentLogs)
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto font-mono text-xs text-gray-300 space-y-0.5">
            @foreach($recentLogs as $line)
            <div class="{{ str_contains($line, 'ERROR') || str_contains($line, 'CRITICAL') ? 'text-red-400' : (str_contains($line, 'WARNING') ? 'text-yellow-400' : 'text-gray-300') }}">{{ $line }}</div>
            @endforeach
        </div>
        @else
        <p class="text-center text-gray-400 py-6 text-sm">No log file found.</p>
        @endif
    </div>
</div>
@endsection
