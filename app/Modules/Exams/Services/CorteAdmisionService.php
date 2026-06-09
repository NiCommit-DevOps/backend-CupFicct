<?php

namespace App\Modules\Exams\Services;

use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Administrative\Models\CupoCarreraConvocatoria;
use App\Modules\Exams\Models\Carrera;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Support\Facades\DB;

/**
 * CU07 — Corte de admisión: ordena a los APROBADO por promedio y asigna ADMITIDO
 * hasta agotar los cupos de cada carrera (cascada 1ª → 2ª opción); el resto queda
 * APROBADO_SIN_CUPO. Es idempotente: re-ejecuta desde la base APROBADO.
 */
class CorteAdmisionService
{
    /** Estado actual del proceso de admisión (sin ejecutar el corte). */
    public function estadoActual(int $idConvocatoria): array
    {
        $this->convocatoria($idConvocatoria);

        return $this->construirResumen($idConvocatoria, $this->cuposEfectivos($idConvocatoria));
    }

    /**
     * Ejecuta el corte: ADMITIDO según promedio y cupos (1ª, luego 2ª opción);
     * sin cupo en ninguna → APROBADO_SIN_CUPO.
     */
    public function ejecutar(int $idConvocatoria): array
    {
        $this->convocatoria($idConvocatoria);

        return DB::transaction(function () use ($idConvocatoria) {
            // 1. Reinicio: deshacer un corte previo (vuelven a APROBADO).
            Inscripcion::where('id_convocatoria', $idConvocatoria)
                ->whereIn('estado_academico', [
                    Inscripcion::ESTADO_ADMITIDO,
                    Inscripcion::ESTADO_APROBADO_SIN_CUPO,
                ])
                ->update([
                    'estado_academico' => Inscripcion::ESTADO_APROBADO,
                    'id_carrera_admitida' => null,
                ]);

            $cupos = $this->cuposEfectivos($idConvocatoria);
            $restante = $cupos;

            // 2. APROBADO ordenados de mayor a menor promedio.
            $aprobados = Inscripcion::where('id_convocatoria', $idConvocatoria)
                ->where('estado_academico', Inscripcion::ESTADO_APROBADO)
                ->with('carreras')
                ->orderByDesc('promedio_final')
                ->orderBy('id_inscripcion')
                ->get();

            foreach ($aprobados as $insc) {
                // Preferencias por orden (1ª, 2ª).
                $asignada = null;
                foreach ($insc->carreras->sortBy(fn ($c) => $c->pivot->orden) as $carrera) {
                    if (($restante[$carrera->id_carrera] ?? 0) > 0) {
                        $asignada = $carrera->id_carrera;
                        break;
                    }
                }

                if ($asignada !== null) {
                    $insc->update([
                        'estado_academico' => Inscripcion::ESTADO_ADMITIDO,
                        'id_carrera_admitida' => $asignada,
                    ]);
                    $restante[$asignada]--;
                } else {
                    $insc->update(['estado_academico' => Inscripcion::ESTADO_APROBADO_SIN_CUPO]);
                }
            }

            return $this->construirResumen($idConvocatoria, $cupos);
        });
    }

    /* ===================== Internos ===================== */

    /** Cupos efectivos por carrera: los de la convocatoria o, si no hay, la plantilla. */
    private function cuposEfectivos(int $idConvocatoria): array
    {
        $porConvocatoria = CupoCarreraConvocatoria::where('id_convocatoria', $idConvocatoria)
            ->pluck('cupos', 'id_carrera');

        $cupos = [];
        foreach (Carrera::all() as $carrera) {
            $cupos[$carrera->id_carrera] = (int) ($porConvocatoria[$carrera->id_carrera] ?? $carrera->cupos);
        }

        return $cupos;
    }

    private function construirResumen(int $idConvocatoria, array $cupos): array
    {
        $porEstado = Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->selectRaw('estado_academico, COUNT(*) AS c')
            ->groupBy('estado_academico')
            ->pluck('c', 'estado_academico');

        $admitidosPorCarrera = Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->where('estado_academico', Inscripcion::ESTADO_ADMITIDO)
            ->whereNotNull('id_carrera_admitida')
            ->selectRaw('id_carrera_admitida, COUNT(*) AS c')
            ->groupBy('id_carrera_admitida')
            ->pluck('c', 'id_carrera_admitida');

        $porCarrera = Carrera::orderBy('nombre')->get()
            ->map(fn (Carrera $c) => [
                'id_carrera' => $c->id_carrera,
                'carrera' => $c->nombre,
                'cupos' => (int) ($cupos[$c->id_carrera] ?? 0),
                'admitidos' => (int) ($admitidosPorCarrera[$c->id_carrera] ?? 0),
            ])
            ->filter(fn ($r) => $r['cupos'] > 0 || $r['admitidos'] > 0)
            ->values();

        return [
            'resumen' => [
                'pendientes_examen' => (int) ($porEstado[Inscripcion::ESTADO_ELEGIBLE] ?? 0),
                'aprobados' => (int) ($porEstado[Inscripcion::ESTADO_APROBADO] ?? 0),
                'admitidos' => (int) ($porEstado[Inscripcion::ESTADO_ADMITIDO] ?? 0),
                'aprobados_sin_cupo' => (int) ($porEstado[Inscripcion::ESTADO_APROBADO_SIN_CUPO] ?? 0),
                'reprobados' => (int) ($porEstado[Inscripcion::ESTADO_REPROBADO] ?? 0),
            ],
            'por_carrera' => $porCarrera,
        ];
    }

    private function convocatoria(int $id): Convocatoria
    {
        return Convocatoria::find($id) ?? abort(404, 'Convocatoria no encontrada.');
    }
}
