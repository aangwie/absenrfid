<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // Cegah N+1 saat development/staging; produksi dibiarkan agar tidak
        // memunculkan 500 hanya karena satu relasi lupa di-eager-load.
        Model::preventLazyLoading(! app()->isProduction());
    }
}
