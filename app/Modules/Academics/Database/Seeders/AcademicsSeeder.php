<?php

namespace App\Modules\Academics\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use Illuminate\Database\Seeder;

class AcademicsSeeder extends Seeder
{
    public function run(): void
    {
        // CU17 / CU10 / CU09 — Permisos del módulo académico (catálogo cerrado).
        $recursosCrud = ['aulas', 'docentes', 'grupos'];

        $permisos = [];

        foreach ($recursosCrud as $recurso) {
            $permisos[] = ['modulo' => "$recurso.index", 'descripcion' => "Listar $recurso"];
            $permisos[] = ['modulo' => "$recurso.store", 'descripcion' => "Crear $recurso"];
            $permisos[] = ['modulo' => "$recurso.update", 'descripcion' => "Actualizar $recurso"];
            $permisos[] = ['modulo' => "$recurso.destroy", 'descripcion' => "Eliminar $recurso"];
        }

        // CU09 — Permiso especial de asignación de postulantes a grupos.
        $permisos[] = ['modulo' => 'grupos.asignar', 'descripcion' => 'Asignar postulantes a grupos'];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(['modulo' => $permiso['modulo']], $permiso);
        }

        // El Administrador hereda los nuevos permisos sin perder los existentes.
        $admin = Rol::where('nombre', 'Administrador')->first();

        if ($admin) {
            $ids = Permiso::whereIn('modulo', array_column($permisos, 'modulo'))->pluck('id_permiso')->all();
            $admin->permisos()->syncWithoutDetaching($ids);
        }

        // El Coordinador Académico solo recibe lectura (.index) de la sección académica.
        $coordinador = Rol::where('nombre', 'Coordinador Académico')->first();

        if ($coordinador) {
            $idsLectura = Permiso::whereIn('modulo', ['aulas.index', 'docentes.index', 'grupos.index'])
                ->pluck('id_permiso')->all();
            $coordinador->permisos()->syncWithoutDetaching($idsLectura);
        }
    }
}
