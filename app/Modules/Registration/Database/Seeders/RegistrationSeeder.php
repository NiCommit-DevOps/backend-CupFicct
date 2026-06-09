<?php

namespace App\Modules\Registration\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use App\Modules\Registration\Models\UnidadEducativa;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    public function run(): void
    {
        // CU04 — Permisos del módulo de registro (catálogo cerrado).
        $recursosCrud = ['postulantes'];

        $permisos = [];

        foreach ($recursosCrud as $recurso) {
            $permisos[] = ['modulo' => "$recurso.index", 'descripcion' => "Listar $recurso"];
            $permisos[] = ['modulo' => "$recurso.store", 'descripcion' => "Crear $recurso"];
            $permisos[] = ['modulo' => "$recurso.update", 'descripcion' => "Actualizar $recurso"];
            $permisos[] = ['modulo' => "$recurso.destroy", 'descripcion' => "Eliminar $recurso"];
        }

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(['modulo' => $permiso['modulo']], $permiso);
        }

        // El Administrador hereda los nuevos permisos.
        $admin = Rol::where('nombre', 'Administrador')->first();

        if ($admin) {
            $ids = Permiso::whereIn('modulo', array_column($permisos, 'modulo'))->pluck('id_permiso')->all();
            $admin->permisos()->syncWithoutDetaching($ids);
        }

        // El Coordinador Académico solo recibe lectura de postulantes.
        $coordinador = Rol::where('nombre', 'Coordinador Académico')->first();

        if ($coordinador) {
            $idLectura = Permiso::where('modulo', 'postulantes.index')->pluck('id_permiso')->all();
            $coordinador->permisos()->syncWithoutDetaching($idLectura);
        }

        // CU04 — Catálogo base de unidades educativas (procedencia de los bachilleres).
        $unidades = [
            ['nombre' => 'Colegio Nacional Florida', 'tipo' => 'Fiscal', 'provincia' => 'Andrés Ibáñez'],
            ['nombre' => 'Colegio La Salle', 'tipo' => 'Privado', 'provincia' => 'Andrés Ibáñez'],
            ['nombre' => 'Colegio Don Bosco', 'tipo' => 'Convenio', 'provincia' => 'Andrés Ibáñez'],
            ['nombre' => 'Unidad Educativa Otro / No listado', 'tipo' => 'Otro', 'provincia' => null],
        ];

        foreach ($unidades as $unidad) {
            UnidadEducativa::firstOrCreate(['nombre' => $unidad['nombre']], $unidad);
        }
    }
}
