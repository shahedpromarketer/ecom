<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** A shop's own backup: its data to its own Google Drive, daily, with Backup now and Restore. */
class BackupController extends Controller
{
    private function tenantId(): int
    {
        $id = auth()->user()->tenant_id ?? \App\Support\Tenancy::id();
        abort_unless($id, 404);

        return (int) $id;
    }

    private function drive(): GoogleDrive
    {
        return GoogleDrive::for($this->tenantId());
    }

    public function index()
    {
        $t = $this->tenantId();
        $drive = $this->drive();
        $files = $csv = [];
        $driveError = null;
        if ($drive->connected()) {
            try {
                $files = $drive->list('.zip');
                $csv = $drive->list('.csv');
            } catch (\Throwable $e) {
                $driveError = $e->getMessage();
            }
        }

        return view('admin.backup.index', [
            'available'  => (bool) GoogleDrive::client(),
            'connected'  => $drive->connected(),
            'email'      => $drive->email(),
            'folder'     => $drive->folderName(),
            'status'     => BackupManager::status($t),
            'task'       => BackupManager::task($t),
            'keep'       => BackupManager::keep($t),
            'files'      => $files,
            'csv'        => $csv[0] ?? null,
            'driveError' => $driveError,
        ]);
    }

    public function connect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('shop_backup_oauth_state', $state);

        try {
            return redirect()->away($this->drive()->authUrl($state));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('shop_backup_oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->state)) {
            return redirect()->route('admin.backup.index')->with('error', 'The Google sign-in expired. Try "Connect Google Drive" again.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('admin.backup.index')->with('error', 'Google Drive was not connected (' . ($request->error ?: 'cancelled') . ').');
        }

        try {
            $this->drive()->exchange($request->code);
            $this->drive()->folderId();
        } catch (\Throwable $e) {
            return redirect()->route('admin.backup.index')->with('error', $e->getMessage());
        }
        ActivityLog::log('backup.drive_connected', \App\Models\Tenant::find($this->tenantId()), 'Google Drive connected for backups (' . ($this->drive()->email() ?? '?') . ') by ' . auth()->user()->name);

        // First backup right away.
        if (! $this->busy()) {
            BackupManager::queue('backup', auth()->id(), null, null, $this->tenantId());
        }

        return redirect()->route('admin.backup.index')->with('success', 'Google Drive connected. Your first backup starts within a minute.');
    }

    public function disconnect()
    {
        $this->drive()->disconnect();
        ActivityLog::log('backup.drive_disconnected', \App\Models\Tenant::find($this->tenantId()), 'Google Drive disconnected from backups by ' . auth()->user()->name);

        return back()->with('success', 'Google Drive disconnected. The backups already there stay in your Drive.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['keep' => 'required|integer|in:' . implode(',', BackupManager::KEEP_OPTIONS)]);
        Setting::setValue('backup_keep', $data['keep'], 'backup', $this->tenantId());

        return back()->with('success', 'Saved: the last ' . $data['keep'] . ' backups are kept.');
    }

    public function backupNow()
    {
        if (! $this->drive()->connected()) {
            return back()->with('error', 'Connect your Google Drive first.');
        }
        if ($this->busy()) {
            return back()->with('error', 'A backup or restore is already waiting or running.');
        }
        BackupManager::queue('backup', auth()->id(), null, null, $this->tenantId());

        return back()->with('success', 'Backup started — it runs within a minute. This page updates by itself.');
    }

    public function restore(Request $request)
    {
        $data = $request->validate(['file_id' => 'required|string|max:200', 'name' => 'nullable|string|max:255', 'confirm' => 'required|string']);
        if (Str::upper(trim($data['confirm'])) !== 'RESTORE') {
            return back()->with('error', 'Type RESTORE to confirm.');
        }
        if ($this->busy()) {
            return back()->with('error', 'A backup or restore is already waiting or running.');
        }
        BackupManager::queue('restore', auth()->id(), $data['file_id'], $data['name'] ?? null, $this->tenantId());
        ActivityLog::log('backup.restore_requested', \App\Models\Tenant::find($this->tenantId()), 'Restore from ' . ($data['name'] ?? $data['file_id']) . ' requested by ' . auth()->user()->name);

        return back()->with('success', 'Restore started — it runs within a minute. Your current data is saved first.');
    }

    public function cancelTask()
    {
        $task = BackupManager::task($this->tenantId());
        if ($task && ($task['state'] ?? '') !== 'running') {
            BackupManager::clearTask($this->tenantId());
        }

        return back();
    }

    public function poll()
    {
        return response()->json(['task' => BackupManager::task($this->tenantId())]);
    }

    private function busy(): bool
    {
        return in_array(BackupManager::task($this->tenantId())['state'] ?? null, ['waiting', 'running'], true);
    }
}
