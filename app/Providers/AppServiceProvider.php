<?php

namespace App\Providers;

use App\Models\User;
use App\Soporte\Permisos;
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
        // Un permiso por operación sensible; la matriz vive en App\Soporte\Permisos.
        foreach (array_keys(Permisos::PERMISOS) as $permiso) {
            Gate::define($permiso, fn (User $usuario) => $usuario->puede($permiso));
        }
    }
}
