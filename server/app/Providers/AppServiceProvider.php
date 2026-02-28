<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Model::unguard();
        Model::preventLazyLoading();
        Model::preventAccessingMissingAttributes();

        $this->app->singleton(
            \App\Contracts\ProgramRegistryInterface::class,
            \App\Services\ProgramRegistry::class,
        );
        $this->app->singleton(
            \App\Contracts\BasePointCalculatorInterface::class,
            \App\Services\BasePointCalculator::class,
        );
        $this->app->singleton(
            \App\Contracts\BonusPointCalculatorInterface::class,
            \App\Services\BonusPointCalculator::class,
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
