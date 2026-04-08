<?php

namespace App\Providers;

use App\Models\Project;
use App\Observers\ProjectObserver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\ProjectListQueryService::class, function () {
            return new \App\Services\ProjectListQueryService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing') && config('database.default') === 'sqlite') {
            $path = database_path('testing.sqlite');
            if (! File::exists($path)) {
                File::put($path, '');
            }
            config([
                'database.connections.sqlite.database' => $path,
                'database.connections.cms' => array_merge(config('database.connections.sqlite'), [
                    'database' => $path,
                ]),
            ]);
        }

        Project::observe(ProjectObserver::class);
    }
}
