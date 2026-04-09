<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Observers\ProjectObserver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->app->singleton(\App\Services\ProjectListQueryService::class, function () {
            return new \App\Services\ProjectListQueryService;
        });

        $this->app->bind(
            \App\Contracts\Repositories\ContractRepositoryInterface::class,
            \App\Repositories\ContractRepository::class
        );
        $this->app->bind(
            \App\Contracts\Repositories\PaymentRepositoryInterface::class,
            \App\Repositories\PaymentRepository::class
        );
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
