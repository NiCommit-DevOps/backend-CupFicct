<?php

namespace App\Modules\Exams\Services;

use App\Modules\Academics\Models\Docente;
use App\Modules\Academics\Models\Grupo;
use App\Modules\Access\Models\Usuario;
use App\Modules\Exams\Models\Horario;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Support\Collection;

/**
 * CU06/CU10 — Horario de clases.
 *
 * - Horario general (por turno) para la página de Materias (staff).
 * - "Mi horario": el docente ve cada grupo que dicta (nombre, horario y aula)
 *   y el postulante su boleta de horario del grupo asignado.
 */
class HorarioService
{
    /**
     * Horario general agrupado por turno: cada turno con la lista de materias,
     * sus días y su rango de horas. Para la sección Horario de Materias.
     *
     * @return array<int,array<string,mixed>>
     */
    public function general(): array
    {
        $porTurno = $this->horariosPorTurno();

        return collect(Horario::TURNOS)
            ->map(fn (string $turno) => [
                'turno' => $turno,
                'clases' => $this->clasesDelTurno($turno, $porTurno),
            ])
            ->all();
    }

    /**
     * Horario del usuario autenticado: lista de grupos con su horario y aula.
     * Docente => sus 1 a 4 grupos; Postulante => su grupo asignado (boleta).
     *
     * @return array<string,mixed>
     */
    public function miHorario(Usuario $usuario): array
    {
        $porTurno = $this->horariosPorTurno();

        $docente = Docente::with(['grupos.aula'])->find($usuario->id_usuario);
        if ($docente) {
            return [
                'rol' => 'Docente',
                'grupos' => $docente->grupos
                    ->map(fn (Grupo $g) => $this->grupoConHorario($g, $porTurno))
                    ->all(),
            ];
        }

        $inscripcion = Inscripcion::with(['grupo.aula'])
            ->where('id_postulante', $usuario->id_usuario)
            ->latest('id_inscripcion')->first();

        $grupos = [];
        if ($inscripcion?->grupo) {
            $grupos[] = $this->grupoConHorario($inscripcion->grupo, $porTurno);
        }

        return ['rol' => 'Postulante', 'grupos' => $grupos];
    }

    /* ===================== Internos ===================== */

    /** Todos los horarios (con materia) agrupados por turno. */
    private function horariosPorTurno(): Collection
    {
        return Horario::with('materia')->get()->groupBy('turno');
    }

    /**
     * Clases (materia + días + horas) de un turno, ordenadas por hora y materia.
     *
     * @return array<int,array<string,mixed>>
     */
    private function clasesDelTurno(?string $turno, Collection $porTurno): array
    {
        return collect($porTurno->get($turno, collect()))
            ->sortBy(fn (Horario $h) => $h->hora_inicio.($h->materia?->nombre ?? ''))
            ->map(fn (Horario $h) => [
                'id_materia' => $h->id_materia,
                'materia' => $h->materia?->nombre,
                'dias' => $h->dias,
                'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $h->hora_fin, 0, 5),
            ])
            ->values()
            ->all();
    }

    /**
     * Un grupo con su nombre, turno, aula y las clases de su turno.
     *
     * @return array<string,mixed>
     */
    private function grupoConHorario(Grupo $grupo, Collection $porTurno): array
    {
        return [
            'id_grupo' => $grupo->id_grupo,
            'sigla' => $grupo->sigla,
            'nombre' => $grupo->nombre,
            'turno' => $grupo->turno,
            'aula' => $grupo->aula ? [
                'nombre' => $grupo->aula->nombre,
                'ubicacion' => $grupo->aula->ubicacion,
            ] : null,
            'clases' => $this->clasesDelTurno($grupo->turno, $porTurno),
        ];
    }
}
