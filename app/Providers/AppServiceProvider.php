<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class
        );
        $this->app->bind(
            \App\Interfaces\TaskRepositoryInterface::class,
            \App\Repositories\TaskRepository::class
        );
        $this->app->bind(
            \App\Interfaces\TaskReminderRepositoryInterface::class,
            \App\Repositories\TaskReminderRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
