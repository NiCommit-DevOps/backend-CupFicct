<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Clase base reutilizable para todos los módulos.
 *
 * Cada módulo crea su propio ServiceProvider que extiende esta clase y solo
 * define su ubicación física. Aquí se concentra el "pegamento" común:
 *  - Carga automática de rutas (routes.php) bajo el prefijo api/v1.
 *  - Carga automática de migraciones del módulo.
 *  - Carga automática de seeders/factories vía PSR-4 (ya resuelto por composer).
 *
 * Esto hace que cada módulo sea autónomo y portable.
 */
abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * Ruta absoluta de la raíz del módulo (normalmente __DIR__ del provider hijo).
     */
    abstract protected function modulePath(): string;

    /**
     * Prefijo de las rutas del módulo.
     */
    protected function routePrefix(): string
    {
        return 'api/v1';
    }

    /**
     * Grupo de middleware aplicado a las rutas del módulo.
     */
    protected function routeMiddleware(): array
    {
        return ['api'];
    }

    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
    }

    protected function loadModuleRoutes(): void
    {
        $routes = $this->modulePath().'/routes.php';

        if (is_file($routes)) {
            Route::middleware($this->routeMiddleware())
                ->prefix($this->routePrefix())
                ->group($routes);
        }
    }

    protected function loadModuleMigrations(): void
    {
        $migrations = $this->modulePath().'/Database/Migrations';

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}
