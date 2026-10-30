<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Developer tool: render admin pages as a user and report time, queries and repeated queries.
 *   php artisan profile:pages admin@shop.test --pages=orders,customers --runs=3
 */
class ProfilePages extends Command
{
    protected $signature   = 'profile:pages {email} {--pages=} {--runs=2} {--show-repeats}';
    protected $description = 'Measure server time and database queries for admin pages';

    private const DEFAULT = [
        'dashboard', 'orders', 'orders/create', 'orders/{order}', 'customers', 'customers/{customer}', 'products',
        'products/create', 'leads', 'failed-orders', 'followups', 'dispatch', 'courier', 'courier-payments', 'inventory',
        'invoices', 'returns', 'payments', 'reports', 'analytics', 'users', 'roles', 'settings', 'integrations/wordpress',
        'categories', 'coupons', 'blacklist', 'activity-log', 'recycle-bin', 'distribution', 'marketing',
        'sidebar/counts',
    ];

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->firstOrFail();
        $pages = $this->option('pages') ? explode(',', $this->option('pages')) : self::DEFAULT;
        $ids = \App\Support\Tenancy::as($user->tenant_id, fn () => [
            '{order}'    => \App\Models\Order::withoutGlobalScope('assignee')->latest('id')->value('id'),
            '{customer}' => \App\Models\Customer::latest('id')->value('id'),
        ]);

        $rows = [];
        foreach ($pages as $page) {
            $uri = '/admin/' . strtr(trim($page), $ids);
            $best = null;
            for ($i = 0; $i < (int) $this->option('runs'); $i++) {
                $r = $this->hit($user, $uri);
                $best = ! $best || $r['ms'] < $best['ms'] ? $r : $best;
            }
            $rows[] = [$uri, $best['status'], $best['ms'], $best['queries'], round($best['db_ms'], 1), $best['repeats'], round($best['kb'])];
            if ($this->option('show-repeats') && $best['top']) {
                foreach ($best['top'] as $sql => $n) {
                    $this->line("   {$n}× " . mb_substr($sql, 0, 160));
                }
            }
        }

        usort($rows, fn ($a, $b) => $b[2] <=> $a[2]);
        $this->table(['Page', 'HTTP', 'ms', 'Queries', 'DB ms', 'Repeated', 'KB'], $rows);

        return self::SUCCESS;
    }

    private function hit(User $user, string $uri): array
    {
        app()->forgetInstance('request');
        Auth::forgetGuards();
        $queries = [];
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request = Request::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => str_contains($uri, 'counts') ? 'application/json' : 'text/html']);
        $request->setLaravelSession($session = app('session')->driver());
        $session->start();
        app()->instance('request', $request);
        Auth::guard('web')->setUser($user);

        $kernel = app(Kernel::class);
        $t = microtime(true);
        $response = $kernel->handle($request);
        $ms = (microtime(true) - $t) * 1000;
        $kernel->terminate($request, $response);

        $log = DB::getQueryLog();
        DB::disableQueryLog();
        $norm = array_map(fn ($q) => preg_replace('/\b\d+\b/', '?', $q['query']), $log);
        $counts = array_count_values($norm);
        arsort($counts);

        return [
            'status'  => $response->getStatusCode(),
            'ms'      => (int) round($ms),
            'queries' => count($log),
            'db_ms'   => array_sum(array_column($log, 'time')),
            'repeats' => array_sum(array_filter($counts, fn ($n) => $n > 1)) - count(array_filter($counts, fn ($n) => $n > 1)),
            'top'     => array_slice(array_filter($counts, fn ($n) => $n > 2), 0, 5, true),
            'kb'      => strlen((string) $response->getContent()) / 1024,
        ];
    }
}
