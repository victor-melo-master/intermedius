<?php

namespace App\Providers;

use App\Models\Banco;
use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\Titular;
use App\Policies\BancoPolicy;
use App\Policies\OperacionPolicy;
use App\Policies\CategoriaGastoPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CuentaPolicy;
use App\Policies\MonedaPolicy;
use App\Policies\TitularPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Application service provider.
 * Registers policy mappings for all models.
 */
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
     * Registers model policies via Gate::policy().
     */
    public function boot(): void
    {
        Gate::policy(Titular::class,       TitularPolicy::class);
        Gate::policy(Banco::class,         BancoPolicy::class);
        Gate::policy(Moneda::class,        MonedaPolicy::class);
        Gate::policy(Cuenta::class,        CuentaPolicy::class);
        Gate::policy(Cliente::class,       ClientePolicy::class);
        Gate::policy(CategoriaGasto::class, CategoriaGastoPolicy::class);
        Gate::policy(Operacion::class,      OperacionPolicy::class);
    }
}
