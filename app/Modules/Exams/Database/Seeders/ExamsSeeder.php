<?php

namespace App\Modules\Exams\Database\Seeders;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use App\Modules\Exams\Models\Horario;
use App\Modules\Exams\Models\Materia;
use Illuminate\Database\Seeder;

class ExamsSeeder extends Seeder
{
    public function run(): void
    {
        // CU06 — Catálogo fijo de materias evaluadas.
        foreach (['Computación', 'Matemáticas', 'Inglés', 'Física'] as $materia) {
            Materia::firstOrCreate(['nombre' => $materia]);
        }

        // CU06/CU10 — Horario de clases por materia y turno (Mañana/Tarde).
        // Los días son fijos por materia; las horas dependen del turno.
        $diasPorMateria = [
            'Computación' => 'LUN-MIE-VIE',
            'Inglés' => 'LUN-MIE-VIE',
            'Matemáticas' => 'MAR-JUE-SAB',
            'Física' => 'MAR-JUE-SAB',
        ];
        $horasPorBloque = [
            'LUN-MIE-VIE' => ['Mañana' => ['07:00', '09:30'], 'Tarde' => ['13:00', '15:30']],
            'MAR-JUE-SAB' => ['Mañana' => ['09:30', '12:00'], 'Tarde' => ['15:30', '18:00']],
        ];

        $idsMateria = Materia::pluck('id_materia', 'nombre');

        foreach ($diasPorMateria as $nombre => $dias) {
            $idMateria = $idsMateria[$nombre] ?? null;
            if ($idMateria === null) {
                continue;
            }
            foreach (Horario::TURNOS as $turno) {
                [$inicio, $fin] = $horasPorBloque[$dias][$turno];
                Horario::firstOrCreate(
                    ['id_materia' => $idMateria, 'turno' => $turno],
                    ['dias' => $dias, 'hora_inicio' => $inicio, 'hora_fin' => $fin],
                );
            }
        }

        // CU08 — Permisos del módulo de exámenes (catálogo cerrado).
        $recursosCrud = ['carreras', 'materias'];

        $permisos = [];

        foreach ($recursosCrud as $recurso) {
            $permisos[] = ['modulo' => "$recurso.index", 'descripcion' => "Listar $recurso"];
            $permisos[] = ['modulo' => "$recurso.store", 'descripcion' => "Crear $recurso"];
            $permisos[] = ['modulo' => "$recurso.update", 'descripcion' => "Actualizar $recurso"];
            $permisos[] = ['modulo' => "$recurso.destroy", 'descripcion' => "Eliminar $recurso"];
        }

        // CU06 — Consulta de exámenes del postulante (solo lectura de sus notas).
        $permisos[] = ['modulo' => 'examenes.rendir', 'descripcion' => 'Consultar mis exámenes y notas'];

        // CU06 — Notas por materia (staff): ver y registrar/editar manualmente.
        $permisos[] = ['modulo' => 'notas.index', 'descripcion' => 'Ver notas de exámenes'];
        $permisos[] = ['modulo' => 'notas.update', 'descripcion' => 'Registrar/editar notas de exámenes'];

        // CU07 — Corte de admisión por cupos.
        $permisos[] = ['modulo' => 'admision.index', 'descripcion' => 'Ver el estado de admisión'];
        $permisos[] = ['modulo' => 'admision.ejecutar', 'descripcion' => 'Ejecutar el corte de admisión'];

        // CU16 — Historial académico (consulta de gestiones concluidas).
        $permisos[] = ['modulo' => 'historial.index', 'descripcion' => 'Consultar el historial académico'];

        // CU12 — Reportes oficiales (acta, padrón, certificados).
        $permisos[] = ['modulo' => 'reportes.index', 'descripcion' => 'Generar reportes oficiales'];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(['modulo' => $permiso['modulo']], $permiso);
        }

        // El Administrador hereda los nuevos permisos sin perder los existentes.
        $admin = Rol::where('nombre', 'Administrador')->first();

        if ($admin) {
            $ids = Permiso::whereIn('modulo', array_column($permisos, 'modulo'))->pluck('id_permiso')->all();
            $admin->permisos()->syncWithoutDetaching($ids);
        }

        // El Coordinador Académico: lectura de carreras + carga manual de notas.
        $coordinador = Rol::where('nombre', 'Coordinador Académico')->first();

        if ($coordinador) {
            $idsCoord = Permiso::whereIn('modulo', [
                'carreras.index',
                'materias.index', 'materias.store', 'materias.update', 'materias.destroy',
                'notas.index', 'notas.update',
                'admision.index', 'admision.ejecutar',
                'historial.index', 'reportes.index',
            ])->pluck('id_permiso')->all();
            $coordinador->permisos()->syncWithoutDetaching($idsCoord);
        }

        // El Postulante puede consultar sus exámenes y notas (solo lectura).
        $postulante = Rol::where('nombre', 'Postulante')->first();

        if ($postulante) {
            $idRendir = Permiso::where('modulo', 'examenes.rendir')->pluck('id_permiso')->all();
            $postulante->permisos()->syncWithoutDetaching($idRendir);
        }

        // CU06/CU10 — Horario de clases: el docente ve sus grupos (nombre,
        // horario y aula) y el postulante su boleta de horario. Permiso propio
        // de estos dos roles (el staff consulta el horario general en Materias).
        $permisoHorario = Permiso::firstOrCreate(
            ['modulo' => 'horario.index'],
            ['descripcion' => 'Consultar mi horario de clases'],
        );

        foreach (['Docente', 'Postulante'] as $nombreRol) {
            Rol::where('nombre', $nombreRol)->first()
                ?->permisos()->syncWithoutDetaching([$permisoHorario->id_permiso]);
        }
    }
}
