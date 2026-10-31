<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The super admin's Google Drive, used only for backups. Scope drive.file: the CRM sees
 * only the files it created itself, nothing else in that Drive.
 *
 * Settings (tenant NULL, encrypted where secret):
 *   backup_google_client  {client_id, client_secret}
 *   backup_google_token   {refresh_token, access_token, expires_at, email}
 *   backup_google_folder  Drive folder id
 */
class GoogleDrive
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';
    public const FOLDER_NAME = 'Ecom Pilot CRM Backups';
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';
    private const CHUNK = 8 * 1024 * 1024;   // multiple of 256 KB

    // ── Settings ───────────────────────────────────────────────────

    public static function client(): ?array
    {
        return self::secret('backup_google_client');
    }

    public static function saveClient(string $id, ?string $secret): void
    {
        $old = self::client();
        self::putSecret('backup_google_client', ['client_id' => trim($id), 'client_secret' => trim((string) $secret) ?: ($old['client_secret'] ?? '')]);
    }

    public static function token(): ?array
    {
        return self::secret('backup_google_token');
    }

    public static function connected(): bool
    {
        return ! empty(self::token()['refresh_token']);
    }

    public static function disconnect(): void
    {
        $t = self::token();
        if (! empty($t['refresh_token'])) {
            rescue(fn () => Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $t['refresh_token']]), null, false);
        }
        Setting::where('tenant_id', null)->whereIn('key', ['backup_google_token', 'backup_google_folder'])->delete();
    }

    public static function redirectUri(): string
    {
        return route('super.backup.callback');
    }

    // ── OAuth ──────────────────────────────────────────────────────

    public static function authUrl(string $state): string
    {
        $c = self::client() ?? throw new RuntimeException('Add the Google Client ID and secret first.');

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $c['client_id'],
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);
    }

    public static function exchange(string $code): array
    {
        $c = self::client() ?? throw new RuntimeException('Google client is not set.');
        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'redirect_uri' => self::redirectUri(), 'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful() || ! $r->json('refresh_token')) {
            throw new RuntimeException('Google did not allow the connection: ' . ($r->json('error_description') ?? $r->json('error') ?? 'no refresh token'));
        }

        $token = [
            'refresh_token' => $r->json('refresh_token'),
            'access_token'  => $r->json('access_token'),
            'expires_at'    => now()->addSeconds((int) $r->json('expires_in', 3600) - 60)->timestamp,
        ];
        self::putSecret('backup_google_token', $token);
        Setting::where('tenant_id', null)->where('key', 'backup_google_folder')->delete();

        $email = rescue(fn () => self::get('/about', ['fields' => 'user(emailAddress)'])->json('user.emailAddress'), null, false);
        self::putSecret('backup_google_token', $token + ['email' => $email]);

        return $token;
    }

    private static function accessToken(): string
    {
        $t = self::token() ?? throw new RuntimeException('Google Drive is not connected.');
        if (! empty($t['access_token']) && ($t['expires_at'] ?? 0) > time()) {
            return $t['access_token'];
        }

        $c = self::client() ?? throw new RuntimeException('Google client is not set.');
        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'refresh_token' => $t['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        if (! $r->successful()) {
            throw new RuntimeException('Google Drive connection expired — connect it again (' . ($r->json('error') ?? $r->status()) . ').');
        }
        $t['access_token'] = $r->json('access_token');
        $t['expires_at'] = now()->addSeconds((int) $r->json('expires_in', 3600) - 60)->timestamp;
        self::putSecret('backup_google_token', $t);

        return $t['access_token'];
    }

    // ── Files ──────────────────────────────────────────────────────

    public static function folderId(): string
    {
        $id = Setting::getValue('backup_google_folder', null, null);
        if ($id) {
            $r = self::get('/files/' . $id, ['fields' => 'id,trashed']);
            if ($r->successful() && ! $r->json('trashed')) {
                return $id;
            }
        }

        $r = self::http()->post(self::API . '/files?fields=id', [
            'name' => self::FOLDER_NAME, 'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        self::check($r, 'create the backup folder');
        Setting::setValue('backup_google_folder', $r->json('id'), 'backup', null);

        return $r->json('id');
    }

    /** Resumable upload in chunks, so big backups work too. Returns the Drive file id. */
    public static function upload(string $path, string $name): string
    {
        $size = filesize($path);
        $start = self::http()->withHeaders(['X-Upload-Content-Type' => 'application/zip', 'X-Upload-Content-Length' => (string) $size])
            ->post(self::UPLOAD . '?uploadType=resumable&fields=id', ['name' => $name, 'parents' => [self::folderId()]]);
        self::check($start, 'start the upload');
        $session = $start->header('Location');

        $fh = fopen($path, 'rb');
        $offset = 0;
        try {
            do {
                $chunk = fread($fh, self::CHUNK);
                $len = strlen($chunk);
                $end = $offset + $len - 1;
                $r = Http::withToken(self::accessToken())->timeout(300)
                    ->withHeaders(['Content-Range' => $size ? "bytes {$offset}-{$end}/{$size}" : 'bytes */0'])
                    ->withBody($chunk, 'application/zip')->put($session);
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

    /** Backups in the folder, newest first: [id, name, size, created]. */
    public static function list(): array
    {
        $r = self::get('/files', [
            'q'        => "'" . self::folderId() . "' in parents and trashed = false",
            'orderBy'  => 'createdTime desc',
            'pageSize' => 200,
            'fields'   => 'files(id,name,size,createdTime,webViewLink)',
        ]);
        self::check($r, 'list the backups');

        return collect($r->json('files', []))->map(fn ($f) => [
            'id' => $f['id'], 'name' => $f['name'], 'size' => (int) ($f['size'] ?? 0),
            'created' => $f['createdTime'] ?? null, 'link' => $f['webViewLink'] ?? null,
        ])->all();
    }

    public static function download(string $id, string $to): void
    {
        $r = Http::withToken(self::accessToken())->timeout(1800)->withOptions(['sink' => $to])
            ->get(self::API . '/files/' . rawurlencode($id), ['alt' => 'media']);
        self::check($r, 'download the backup');
    }

    public static function delete(string $id): void
    {
        self::http()->delete(self::API . '/files/' . rawurlencode($id));
    }

    // ── Helpers ────────────────────────────────────────────────────

    private static function http()
    {
        return Http::withToken(self::accessToken())->acceptJson()->timeout(60);
    }

    private static function get(string $uri, array $query = [])
    {
        return self::http()->get(self::API . $uri, $query);
    }

    private static function check($r, string $what): void
    {
        if (! $r->successful()) {
            throw new RuntimeException('Google Drive: could not ' . $what . ' (' . $r->status() . ' ' . ($r->json('error.message') ?? '') . ').');
        }
    }

    private static function secret(string $key): ?array
    {
        $v = Setting::getValue($key, null, null);
        if (! $v) {
            return null;
        }

        return rescue(fn () => json_decode(Crypt::decryptString($v), true), null, false);
    }

    private static function putSecret(string $key, array $value): void
    {
        Setting::setValue($key, Crypt::encryptString(json_encode($value)), 'backup', null);
    }
}
