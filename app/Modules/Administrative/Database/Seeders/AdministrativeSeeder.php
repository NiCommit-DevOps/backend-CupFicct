<?php

namespace App\Modules\Administrative\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
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

        // No se crean gestiones ni convocatorias por defecto: el sistema queda
        // limpio y el administrador las da de alta manualmente (CU19).
    }
}
