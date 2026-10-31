<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Super Admin → Backup: connect Google Drive, daily backups, Backup now, Restore. */
class BackupController extends Controller
{
    public function index()
    {
        $driveFiles = [];
        $driveError = null;
        if (GoogleDrive::for(null)->connected()) {
            try {
                $driveFiles = GoogleDrive::for(null)->list();
            } catch (\Throwable $e) {
                $driveError = $e->getMessage();
            }
        }

        return view('super-admin.backup', [
            'client'     => GoogleDrive::client(),
            'connected'  => GoogleDrive::for(null)->connected(),
            'email'      => GoogleDrive::for(null)->email(),
            'redirect'   => GoogleDrive::for(null)->redirectUri(),
            'status'     => BackupManager::status(),
            'task'       => BackupManager::task(),
            'keep'       => BackupManager::keep(),
            'files'      => $driveFiles,
            'driveError' => $driveError,
            'local'      => BackupManager::localCopies(),
        ]);
    }

    public function saveClient(Request $request)
    {
        $data = $request->validate([
            'client_id'     => 'required|string|max:255|regex:/\.apps\.googleusercontent\.com$/',
            'client_secret' => [GoogleDrive::client() ? 'nullable' : 'required', 'string', 'max:255'],
        ], ['client_id.regex' => 'The Client ID ends with .apps.googleusercontent.com']);
        GoogleDrive::saveClient($data['client_id'], $data['client_secret'] ?? null);

        return back()->with('success', 'Google app saved. Now press "Connect Google Drive".');
    }

    public function connect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('backup_oauth_state', $state);

        try {
            return redirect()->away(GoogleDrive::for(null)->authUrl($state));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('backup_oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->state)) {
            return redirect()->route('super.backup')->with('error', 'The Google sign-in expired. Try "Connect Google Drive" again.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('super.backup')->with('error', 'Google Drive was not connected (' . ($request->error ?: 'cancelled') . ').');
        }

        try {
            GoogleDrive::for(null)->exchange($request->code);
            GoogleDrive::for(null)->folderId();
        } catch (\Throwable $e) {
            return redirect()->route('super.backup')->with('error', $e->getMessage());
        }
        $this->log('backup.drive_connected', 'Google Drive connected for backups (' . (GoogleDrive::for(null)->email() ?? '?') . ')');

        return redirect()->route('super.backup')->with('success', 'Google Drive connected. Press "Backup now" to make the first backup.');
    }

    public function disconnect()
    {
        GoogleDrive::for(null)->disconnect();
        $this->log('backup.drive_disconnected', 'Google Drive disconnected from backups');

        return back()->with('success', 'Google Drive disconnected. Backups stay in your Drive.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['keep' => 'required|integer|in:' . implode(',', BackupManager::KEEP_OPTIONS)]);
        Setting::setValue('backup_keep', $data['keep'], 'backup', null);

        return back()->with('success', 'Saved: the last ' . $data['keep'] . ' backups are kept.');
    }

    public function backupNow()
    {
        if ($this->busy()) {
            return back()->with('error', 'A backup or restore is already waiting or running.');
        }
        BackupManager::queue('backup', auth()->id());

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
        BackupManager::queue('restore', auth()->id(), $data['file_id'], $data['name'] ?? null);
        $this->log('backup.restore_requested', 'Restore requested from ' . ($data['name'] ?? $data['file_id']));

        return back()->with('success', 'Restore started — it runs within a minute. The current data is saved first.');
    }

    public function cancelTask()
    {
        $task = BackupManager::task();
        if ($task && ($task['state'] ?? '') !== 'running') {
            Setting::where('tenant_id', null)->where('key', 'backup_task')->delete();
        }

        return back();
    }

    /** For the page to refresh itself while something runs. */
    public function poll()
    {
        return response()->json(['task' => BackupManager::task(), 'status' => BackupManager::status()]);
    }

    private function busy(): bool
    {
        return in_array(BackupManager::task()['state'] ?? null, ['waiting', 'running'], true);
    }

    private function log(string $action, string $text): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(), 'action' => $action, 'action_performed' => $action,
            'subject_type' => 'backup', 'subject_id' => 0, 'description' => $text,
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'tenant_id' => null,
        ]);
    }
}
