<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($ability === 'viewLogViewer') {
                return $user->hasRole('owner') ? true : false;
            }

            return ($user->hasRole('owner') || $user->hasRole('super-admin')) ? true : null;
        });

        Gate::define('viewLogViewer', function ($user) {
            return $user->hasRole('owner');
        });

        if ($this->app->runningInConsole()) {
            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }

            if (empty($_ENV['TEMP'])) {
                $_ENV['TEMP'] = $tempDir;
            }
            if (empty($_ENV['TMP'])) {
                $_ENV['TMP'] = $tempDir;
            }
            putenv("TEMP={$tempDir}");
            putenv("TMP={$tempDir}");

            if (class_exists(ServeCommand::class)) {
                ServeCommand::$passthroughVariables = array_unique(array_merge(
                    ServeCommand::$passthroughVariables,
                    ['TEMP', 'TMP', 'USERPROFILE', 'LOCALAPPDATA', 'APPDATA', 'SYSTEMROOT', 'SYSTEMDRIVE', 'WINDIR']
                ));
            }

            if (PHP_OS_FAMILY === 'Windows' && class_exists(DevCommands::class)) {
                DevCommands::artisan('serve', 'server');
                DevCommands::except('vite');
            }
        }
    }
}
