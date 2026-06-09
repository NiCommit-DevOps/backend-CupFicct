<?php

namespace App\Modules\Academics;

use App\Modules\Access\Models\Usuario;
use App\Modules\Academics\Observers\UsuarioObserver;
use App\Providers\BaseModuleServiceProvider;

class AcademicsModuleServiceProvider extends BaseModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // CU10 — Sincroniza la especialización Docente con el rol del usuario.
        Usuario::observe(UsuarioObserver::class);
    }

    protected function modulePath(): string
    {
        return __DIR__;
    }
}
