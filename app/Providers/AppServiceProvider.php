<?php

namespace App\Providers;

use App\Services\Facturacion\CalculadoraIgv;
use App\Services\Facturacion\ServicioSunat;
use App\Services\Facturacion\ServicioSunatGreenter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CalculadoraIgv::class, fn () => new CalculadoraIgv((float) config('sunat.igv')));
        $this->app->bind(ServicioSunat::class, ServicioSunatGreenter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // La plantilla SB Admin usa Bootstrap: la paginación también.
        Paginator::useBootstrapFive();
    }
}
