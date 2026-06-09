<?php

namespace App\Modules\Exams\Services;

use App\Modules\Access\Models\Usuario;
use App\Modules\Exams\Models\Materia;
use App\Modules\Registration\Models\Inscripcion;

/**
 * CU06 — Consulta de exámenes del postulante (solo lectura).
 *
 * El postulante no rinde examen ni accede a fechas: únicamente consulta las
 * notas por materia que el staff cargó, su promedio y su estado final.
 */
class ExamenAlumnoService
{
    private const TOTAL_EXAMENES = 3;

    /**
     * Resultados del postulante autenticado: notas por materia en cada examen,
     * promedio por examen, promedio final y estado.
     *
     * @return array<string,mixed>
     */
    public function misResultados(Usuario $usuario): array
    {
        $inscripcion = $this->inscripcion($usuario);

        $materias = Materia::orderBy('id_materia')->get();

        $examenes = [];
        for ($n = 1; $n <= self::TOTAL_EXAMENES; $n++) {
            $evaluacion = $inscripcion->evaluaciones->firstWhere('numero_examen', $n);
            $notas = $evaluacion ? $evaluacion->notasMaterias->keyBy('id_materia') : collect();

            $examenes[] = [
                'numero_examen' => $n,
                'promedio' => $evaluacion?->nota !== null ? (float) $evaluacion->nota : null,
                'materias' => $materias->map(fn (Materia $m) => [
                    'id_materia' => $m->id_materia,
                    'nombre' => $m->nombre,
                    'nota' => $notas->get($m->id_materia)?->nota !== null
                        ? (float) $notas->get($m->id_materia)->nota
                        : null,
                ])->values(),
            ];
        }

        return [
            'materias' => $materias->map(fn (Materia $m) => [
                'id_materia' => $m->id_materia,
                'nombre' => $m->nombre,
            ])->values(),
            'examenes' => $examenes,
            'promedio_final' => $inscripcion->promedio_final !== null ? (float) $inscripcion->promedio_final : null,
            'estado_academico' => $inscripcion->estado_academico,
        ];
    }

    /* ===================== Internos ===================== */

    /** Inscripción del postulante (habilitada o ya con resultado). */
    private function inscripcion(Usuario $usuario): Inscripcion
    {
        $inscripcion = Inscripcion::where('id_postulante', $usuario->id_usuario)
            ->with(['evaluaciones.notasMaterias'])
            ->latest('id_inscripcion')->first()
            ?? abort(403, 'No tienes una inscripción para consultar exámenes.');

        $habilitados = [
            Inscripcion::ESTADO_ELEGIBLE,
            Inscripcion::ESTADO_APROBADO,
            Inscripcion::ESTADO_REPROBADO,
            Inscripcion::ESTADO_ADMITIDO,
            Inscripcion::ESTADO_APROBADO_SIN_CUPO,
        ];
        if (! in_array($inscripcion->estado_academico, $habilitados, true)) {
            abort(403, 'Aún no estás habilitado. Completa el pago de tu inscripción.');
        }

        return $inscripcion;
    }
}
