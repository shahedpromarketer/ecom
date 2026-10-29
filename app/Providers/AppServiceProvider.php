<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\Support\Tenancy;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        // A tenant pinned with Tenancy::set() must never leak into the next
        // request or queued job handled by the same PHP process.
        $this->app->terminating(fn () => Tenancy::forget());
        Event::listen(JobProcessing::class, fn () => Tenancy::forget());
        Event::listen(JobProcessed::class, fn () => Tenancy::forget());

        // @can('view_orders') etc. in Blade use the user's role permissions.
        // Shop owners/admins and super admins pass every check.
        \Illuminate\Support\Facades\Gate::before(function ($user, string $ability) {
            if (in_array($ability, \App\Support\Permissions::all(), true)) {
                return $user->hasPermission($ability);
            }

            return null;
        });
    }
}
