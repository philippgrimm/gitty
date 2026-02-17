<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Git\GitCacheService;
use App\Services\NotificationService;
use App\Services\RepoManager;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GitCacheService::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(RepoManager::class);
        $this->app->singleton(NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
