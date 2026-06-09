<?php

namespace App\Modules\Administrative\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Administrative\Models\Gestion;
use Illuminate\Database\Seeder;

class AdministrativeSeeder extends Seeder
{
    public function run(): void
    {
        // CU19 — Permisos del módulo administrativo (catálogo cerrado).
        $recursosCrud = ['gestiones', 'convocatorias'];

        $permisos = [];

        foreach ($recursosCrud as $recurso) {
            $permisos[] = ['modulo' => "$recurso.index", 'descripcion' => "Listar $recurso"];
            $permisos[] = ['modulo' => "$recurso.store", 'descripcion' => "Crear $recurso"];
            $permisos[] = ['modulo' => "$recurso.update", 'descripcion' => "Actualizar $recurso"];
            $permisos[] = ['modulo' => "$recurso.destroy", 'descripcion' => "Eliminar $recurso"];
        }

        // CU13 — Dashboard administrativo (gerencial).
        $permisos[] = ['modulo' => 'dashboard.index', 'descripcion' => 'Ver el dashboard administrativo'];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(['modulo' => $permiso['modulo']], $permiso);
        }

        // El Administrador hereda los nuevos permisos sin perder los existentes.
        $admin = Rol::where('nombre', 'Administrador')->first();

        if ($admin) {
            $ids = Permiso::whereIn('modulo', array_column($permisos, 'modulo'))->pluck('id_permiso')->all();
            $admin->permisos()->syncWithoutDetaching($ids);
        }

        // El Coordinador Académico también accede al dashboard (toma de decisiones).
        $coordinador = Rol::where('nombre', 'Coordinador Académico')->first();

        if ($coordinador) {
            $idDashboard = Permiso::where('modulo', 'dashboard.index')->pluck('id_permiso')->all();
            $coordinador->permisos()->syncWithoutDetaching($idDashboard);
        }

        // Gestión activa + convocatoria abierta por defecto, para habilitar el
        // registro de postulantes (CU04) de inmediato.
        $anio = (int) date('Y');

        $gestion = Gestion::firstOrCreate(
            ['nombre' => "Gestión {$anio}"],
            [
                'fecha_inicio' => "{$anio}-01-01",
                'fecha_fin' => "{$anio}-12-31",
                'estado' => Gestion::ESTADO_ACTIVA,
            ]
        );

        Convocatoria::firstOrCreate(
            ['id_gestion' => $gestion->id_gestion, 'nombre' => "Primer PSA {$anio}"],
            [
                'fecha_creacion' => now()->toDateString(),
                'fecha_limite_inscripcion' => "{$anio}-12-31",
                'estado' => Convocatoria::ESTADO_ABIERTA,
            ]
        );
    }
}
