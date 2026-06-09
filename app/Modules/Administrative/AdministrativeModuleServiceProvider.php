<?php

namespace App\Modules\Administrative;

use App\Modules\Academics\Models\Grupo;
use App\Modules\Access\Models\Rol;
use App\Modules\Access\Models\Usuario;
use App\Modules\Administrative\Models\CupoCarreraConvocatoria;
use App\Modules\Administrative\Observers\BitacoraObserver;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\Pago;
use App\Providers\BaseModuleServiceProvider;

class AdministrativeModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * CU11 — Modelos críticos auditados (accesos, cupos y pagos). Cada cambio
     * (INSERT/UPDATE/DELETE) sobre ellos se registra en la bitácora.
     */
    private const MODELOS_AUDITADOS = [
        Usuario::class,
        Rol::class,
        Inscripcion::class,
        Grupo::class,
        Pago::class,
        CupoCarreraConvocatoria::class,
    ];

    public function boot(): void
    {
        parent::boot();

        foreach (self::MODELOS_AUDITADOS as $modelo) {
            $modelo::observe(BitacoraObserver::class);
        }
    }

    protected function modulePath(): string
    {
        return __DIR__;
    }
}
