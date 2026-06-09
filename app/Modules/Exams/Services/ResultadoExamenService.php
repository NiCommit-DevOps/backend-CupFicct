<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\Evaluacion;
use App\Modules\Exams\Models\Materia;
use App\Modules\Exams\Models\NotaMateria;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Support\Facades\DB;

/**
 * CU06 — Carga manual de notas por materia (Admin / Coordinador Académico).
 *
 * Por cada postulante el staff ingresa, en cada uno de los 3 exámenes, la nota
 * (0-100) de cada materia (Computación, Matemáticas, Inglés, Física). El sistema
 * calcula automáticamente el promedio y el estado:
 *  - Si el postulante reprueba CUALQUIER materia (nota < 60) en CUALQUIER examen,
 *    queda REPROBADO de inmediato.
 *  - Si completa los 3 exámenes con todas las materias >= 60, queda APROBADO.
 */
class ResultadoExamenService
{
    private const TOTAL_EXAMENES = 3;

    /**
     * @param  array{id_convocatoria?:?int,id_gestion?:?int,buscar?:?string}  $filtros
     * @return array{materias:array<int,array{id_materia:int,nombre:string}>, inscripciones:array<int,array<string,mixed>>}
     */
    public function listar(array $filtros): array
    {
        $inscripciones = Inscripcion::query()
            ->with(['postulante.usuario', 'evaluaciones.notasMaterias'])
            ->when($filtros['id_convocatoria'] ?? null, fn ($q, $id) => $q->where('id_convocatoria', $id))
            ->when($filtros['id_gestion'] ?? null, fn ($q, $id) => $q->whereHas(
                'convocatoria', fn ($c) => $c->where('id_gestion', $id),
            ))
            ->when($filtros['buscar'] ?? null, fn ($q, $b) => $q->whereHas(
                'postulante.usuario',
                fn ($u) => $u->where('nombres', 'ILIKE', "%{$b}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$b}%")
                    ->orWhere('ci', 'ILIKE', "%{$b}%"),
            ))
            ->orderBy('id_inscripcion')
            ->get();

        return [
            'materias' => $this->materias()->map(fn (Materia $m) => [
                'id_materia' => $m->id_materia,
                'nombre' => $m->nombre,
            ])->values()->all(),
            'inscripciones' => $inscripciones->map(fn (Inscripcion $i) => $this->fila($i))->all(),
        ];
    }

    /**
     * Registra/edita las notas por materia de un postulante y recalcula su
     * promedio y estado.
     *
     * @param  array<int,array{numero_examen:int,id_materia:int,nota:?float}>  $items
     */
    public function guardarNotas(int $idInscripcion, array $items): array
    {
        $inscripcion = Inscripcion::with(['postulante.usuario', 'evaluaciones.notasMaterias'])
            ->find($idInscripcion) ?? abort(404, 'Inscripción no encontrada.');

        DB::transaction(function () use ($inscripcion, $items) {
            foreach ($items as $item) {
                $numero = (int) $item['numero_examen'];
                $idMateria = (int) $item['id_materia'];
                $nota = $item['nota'] ?? null;

                $evaluacion = Evaluacion::firstOrCreate([
                    'id_inscripcion' => $inscripcion->id_inscripcion,
                    'numero_examen' => $numero,
                ]);

                if ($nota === null || $nota === '') {
                    // Borra la nota de esa materia en ese examen.
                    NotaMateria::where('id_evaluacion', $evaluacion->id_evaluacion)
                        ->where('id_materia', $idMateria)->delete();

                    continue;
                }

                NotaMateria::updateOrCreate(
                    ['id_evaluacion' => $evaluacion->id_evaluacion, 'id_materia' => $idMateria],
                    ['nota' => round((float) $nota, 2)],
                );
            }

            $this->recalcular($inscripcion->id_inscripcion);
        });

        return $this->fila(
            Inscripcion::with(['postulante.usuario', 'evaluaciones.notasMaterias'])->find($idInscripcion)
        );
    }

    /* ===================== Internos ===================== */

    /** @return \Illuminate\Support\Collection<int,Materia> */
    private function materias()
    {
        return Materia::orderBy('id_materia')->get();
    }

    private function fila(Inscripcion $insc): array
    {
        $usuario = $insc->postulante?->usuario;
        $materias = $this->materias();

        // numero_examen => (id_materia => nota)
        $examenes = [];
        for ($n = 1; $n <= self::TOTAL_EXAMENES; $n++) {
            $evaluacion = $insc->evaluaciones->firstWhere('numero_examen', $n);
            $notas = $evaluacion
                ? $evaluacion->notasMaterias->keyBy('id_materia')
                : collect();

            $porMateria = [];
            foreach ($materias as $m) {
                $valor = $notas->get($m->id_materia)?->nota;
                $porMateria[$m->id_materia] = $valor !== null ? (float) $valor : null;
            }

            $examenes[$n] = [
                'numero_examen' => $n,
                'promedio' => $evaluacion?->nota !== null ? (float) $evaluacion->nota : null,
                'materias' => $porMateria,
            ];
        }

        return [
            'id_inscripcion' => $insc->id_inscripcion,
            'postulante' => $usuario ? [
                'ci' => $usuario->ci,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
            ] : null,
            'examenes' => $examenes,
            'promedio_final' => $insc->promedio_final !== null ? (float) $insc->promedio_final : null,
            'estado_academico' => $insc->estado_academico,
        ];
    }

    /**
     * Recalcula promedio por examen, promedio final y estado del postulante,
     * aplicando la regla: reprueba una materia (nota < 60) → REPROBADO.
     */
    private function recalcular(int $idInscripcion): void
    {
        $inscripcion = Inscripcion::with('evaluaciones.notasMaterias')->find($idInscripcion);
        if (! $inscripcion) {
            return;
        }

        $totalMaterias = (int) Materia::count();
        $umbral = Inscripcion::NOTA_APROBACION; // 60

        $todasLasNotas = [];

        foreach ($inscripcion->evaluaciones as $evaluacion) {
            $notas = $evaluacion->notasMaterias->pluck('nota')->map(fn ($v) => (float) $v)->all();
            $todasLasNotas = array_merge($todasLasNotas, $notas);

            // El examen tiene promedio solo cuando están todas sus materias.
            $promedioExamen = ($totalMaterias > 0 && count($notas) === $totalMaterias)
                ? round(array_sum($notas) / $totalMaterias, 2)
                : null;

            $evaluacion->update(['nota' => $promedioExamen]);
        }

        // Sin notas cargadas: revierte un estado de examen previo.
        if (count($todasLasNotas) === 0) {
            if (in_array($inscripcion->estado_academico, [Inscripcion::ESTADO_APROBADO, Inscripcion::ESTADO_REPROBADO], true)) {
                $inscripcion->update(['promedio_final' => null, 'estado_academico' => Inscripcion::ESTADO_ELEGIBLE]);
            }

            return;
        }

        $hayReprobada = collect($todasLasNotas)->contains(fn ($n) => $n < $umbral);
        $totalEsperado = $totalMaterias * self::TOTAL_EXAMENES;
        $completo = count($todasLasNotas) >= $totalEsperado;
        $promedio = round(array_sum($todasLasNotas) / count($todasLasNotas), 2);

        if ($hayReprobada) {
            $estado = Inscripcion::ESTADO_REPROBADO;
        } elseif ($completo) {
            $estado = Inscripcion::ESTADO_APROBADO;
        } else {
            // Carga en progreso, sin reprobadas aún.
            $estado = Inscripcion::ESTADO_ELEGIBLE;
        }

        $inscripcion->update([
            'promedio_final' => $promedio,
            'estado_academico' => $estado,
        ]);
    }
}
