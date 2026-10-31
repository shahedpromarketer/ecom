<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A Google Drive used only for backups: the super admin's (tenant null) or a shop's own.
 * Scope drive.file: the CRM sees only the files it created itself, nothing else in that Drive.
 *
 * Settings (encrypted where secret):
 *   backup_google_client  {client_id, client_secret}   super admin only, used by every shop too
 *   backup_google_token   {refresh_token, access_token, expires_at, email}   per owner
 *   backup_google_folder  Drive folder id   per owner
 */
class GoogleDrive
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';
    private const CHUNK = 8 * 1024 * 1024;   // multiple of 256 KB

    public function __construct(public readonly ?int $tenantId = null) {}

    public static function for(?int $tenantId): self
    {
        return new self($tenantId);
    }

    public function folderName(): string
    {
        return $this->tenantId ? 'Ecom Pilot Backups' : 'Ecom Pilot CRM Backups';
    }

    // ── The Google app (super admin sets it once) ──────────────────

    public static function client(): ?array
    {
        return self::secret('backup_google_client', null);
    }

    public static function saveClient(string $id, ?string $secret): void
    {
        $old = self::client();
        self::putSecret('backup_google_client', null, ['client_id' => trim($id), 'client_secret' => trim((string) $secret) ?: ($old['client_secret'] ?? '')]);
    }

    // ── This owner's connection ────────────────────────────────────

    public function token(): ?array
    {
        return self::secret('backup_google_token', $this->tenantId);
    }

    public function connected(): bool
    {
        return ! empty($this->token()['refresh_token']);
    }

    public function email(): ?string
    {
        return $this->token()['email'] ?? null;
    }

    public function disconnect(): void
    {
        $t = $this->token();
        if (! empty($t['refresh_token'])) {
            rescue(fn () => Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $t['refresh_token']]), null, false);
        }
        Setting::where('tenant_id', $this->tenantId)->whereIn('key', ['backup_google_token', 'backup_google_folder'])->delete();
    }

    public function redirectUri(): string
    {
        return route($this->tenantId ? 'admin.backup.callback' : 'super.backup.callback');
    }

    public function authUrl(string $state): string
    {
        $c = self::client() ?? throw new RuntimeException($this->tenantId
            ? 'Google Drive backup is not set up on this CRM yet — please contact support.'
            : 'Add the Google Client ID and secret first.');

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $c['client_id'],
            'redirect_uri'  => $this->redirectUri(),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);
    }

    public function exchange(string $code): void
    {
        $c = self::client() ?? throw new RuntimeException('Google app is not set.');
        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'redirect_uri' => $this->redirectUri(), 'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful() || ! $r->json('refresh_token')) {
            throw new RuntimeException('Google did not allow the connection: ' . ($r->json('error_description') ?? $r->json('error') ?? 'no refresh token'));
        }

        $token = [
            'refresh_token' => $r->json('refresh_token'),
            'access_token'  => $r->json('access_token'),
            'expires_at'    => now()->addSeconds((int) $r->json('expires_in', 3600) - 60)->timestamp,
        ];
        self::putSecret('backup_google_token', $this->tenantId, $token);
        Setting::where('tenant_id', $this->tenantId)->where('key', 'backup_google_folder')->delete();

        $email = rescue(fn () => $this->get('/about', ['fields' => 'user(emailAddress)'])->json('user.emailAddress'), null, false);
        self::putSecret('backup_google_token', $this->tenantId, $token + ['email' => $email]);
    }

    private function accessToken(): string
    {
        $t = $this->token() ?? throw new RuntimeException('Google Drive is not connected.');
        if (! empty($t['access_token']) && ($t['expires_at'] ?? 0) > time()) {
            return $t['access_token'];
        }

        $c = self::client() ?? throw new RuntimeException('Google app is not set.');
        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'refresh_token' => $t['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        if (! $r->successful()) {
            throw new RuntimeException('Google Drive connection expired — connect it again (' . ($r->json('error') ?? $r->status()) . ').');
        }
        $t['access_token'] = $r->json('access_token');
        $t['expires_at'] = now()->addSeconds((int) $r->json('expires_in', 3600) - 60)->timestamp;
        self::putSecret('backup_google_token', $this->tenantId, $t);

        return $t['access_token'];
    }

    // ── Files ──────────────────────────────────────────────────────

    public function folderId(): string
    {
        $id = Setting::getValue('backup_google_folder', null, $this->tenantId);
        if ($id) {
            $r = $this->get('/files/' . $id, ['fields' => 'id,trashed']);
            if ($r->successful() && ! $r->json('trashed')) {
                return $id;
            }
        }

        $r = $this->http()->post(self::API . '/files?fields=id', [
            'name' => $this->folderName(), 'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        self::check($r, 'create the backup folder');
        Setting::setValue('backup_google_folder', $r->json('id'), 'backup', $this->tenantId);

        return $r->json('id');
    }

    /** Resumable upload in chunks, so big backups work too. Returns the Drive file id. */
    public function upload(string $path, string $name, string $mime = 'application/zip'): string
    {
        $size = filesize($path);
        $start = $this->http()->withHeaders(['X-Upload-Content-Type' => $mime, 'X-Upload-Content-Length' => (string) $size])
            ->post(self::UPLOAD . '?uploadType=resumable&fields=id', ['name' => $name, 'parents' => [$this->folderId()]]);
        self::check($start, 'start the upload');
        $session = $start->header('Location');

        $fh = fopen($path, 'rb');
        $offset = 0;
        try {
            do {
                $chunk = fread($fh, self::CHUNK);
                $len = strlen($chunk);
                $end = $offset + $len - 1;
                $r = Http::withToken($this->accessToken())->timeout(300)
                    ->withHeaders(['Content-Range' => $size ? "bytes {$offset}-{$end}/{$size}" : 'bytes */0'])
                    ->withBody($chunk, $mime)->put($session);
                if ($r->status() === 308) {
                    $range = $r->header('Range');   // bytes=0-12345
                    $offset = $range ? ((int) substr($range, strrpos($range, '-') + 1)) + 1 : $offset + $len;
                    fseek($fh, $offset);
                    continue;
                }
                self::check($r, 'upload the backup');

                return (string) $r->json('id');
            } while (! feof($fh) || $offset < $size);
        } finally {
            fclose($fh);
        }

        throw new RuntimeException('The upload did not finish.');
    }

    /** Files in the folder, newest first: [id, name, size, created, link]. $ext filters by name ending. */
    public function list(?string $ext = '.zip'): array
    {
        $r = $this->get('/files', [
            'q'        => "'" . $this->folderId() . "' in parents and trashed = false",
            'orderBy'  => 'createdTime desc',
            'pageSize' => 500,
            'fields'   => 'files(id,name,size,createdTime,webViewLink)',
        ]);
        self::check($r, 'list the backups');

        return collect($r->json('files', []))
            ->filter(fn ($f) => ! $ext || str_ends_with($f['name'], $ext))
            ->map(fn ($f) => [
                'id' => $f['id'], 'name' => $f['name'], 'size' => (int) ($f['size'] ?? 0),
                'created' => $f['createdTime'] ?? null, 'link' => $f['webViewLink'] ?? null,
            ])->values()->all();
    }

    public function download(string $id, string $to): void
    {
        $r = Http::withToken($this->accessToken())->timeout(1800)->withOptions(['sink' => $to])
            ->get(self::API . '/files/' . rawurlencode($id), ['alt' => 'media']);
        self::check($r, 'download the backup');
    }

    public function delete(string $id): void
    {
        $this->http()->delete(self::API . '/files/' . rawurlencode($id));
    }

    /** Keep the newest $keep files of each kind (.zip, .csv). */
    public function prune(int $keep): void
    {
        foreach (['.zip', '.csv'] as $ext) {
            foreach (array_slice($this->list($ext), $keep) as $old) {
                $this->delete($old['id']);
            }
        }
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function http()
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(60);
    }

    private function get(string $uri, array $query = [])
    {
        return $this->http()->get(self::API . $uri, $query);
    }

    private static function check($r, string $what): void
    {
        if (! $r->successful()) {
            throw new RuntimeException('Google Drive: could not ' . $what . ' (' . $r->status() . ' ' . ($r->json('error.message') ?? '') . ').');
        }
    }

    private static function secret(string $key, ?int $tenantId): ?array
    {
        $v = Setting::getValue($key, null, $tenantId);
        if (! $v) {
            return null;
        }

        return rescue(fn () => json_decode(Crypt::decryptString($v), true), null, false);
    }

    private static function putSecret(string $key, ?int $tenantId, array $value): void
    {
        Setting::setValue($key, Crypt::encryptString(json_encode($value)), 'backup', $tenantId);
    }
}
