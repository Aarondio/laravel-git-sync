<?php

namespace Aaronidikko\GitSync;

use Aaronidikko\GitSync\Commands\GitSyncCommand;
use Aaronidikko\GitSync\Commands\GitSyncInstallCommand;
use Illuminate\Support\ServiceProvider;

class GitSyncServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/git-sync.php',
            'git-sync'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/git-sync.php' => config_path('git-sync.php'),
        ], 'git-sync-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GitSyncCommand::class,
                GitSyncInstallCommand::class,
            ]);
        }
    }
}
