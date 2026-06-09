<?php

namespace App\Modules\Exams\Services;

use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;

/**
 * CU16 — Historial académico: consulta de solo lectura sobre convocatorias
 * CONCLUIDA, buscando por CI o código de trámite.
 */
class HistorialAcademicoService
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function buscar(string $termino): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        $inscripciones = Inscripcion::query()
            // Solo gestiones cerradas: convocatorias CONCLUIDA.
            ->whereHas('convocatoria', fn ($q) => $q->where('estado', Convocatoria::ESTADO_CONCLUIDA))
            ->where(function ($w) use ($termino) {
                $w->whereHas('postulante.usuario', fn ($u) => $u->where('ci', 'ILIKE', $termino));
                if (ctype_digit($termino)) {
                    $w->orWhereHas('postulante', fn ($p) => $p->where('codigo_tramite', (int) $termino));
                }
            })
            ->with(['postulante.usuario', 'convocatoria.gestion', 'evaluaciones', 'carreraAdmitida'])
            ->orderByDesc('id_inscripcion')
            ->get();

        return $inscripciones->map(fn (Inscripcion $i) => $this->fila($i))->all();
    }

    private function fila(Inscripcion $insc): array
    {
        $usuario = $insc->postulante?->usuario;
        // Nota de cada examen = promedio de sus materias.
        $notas = $insc->evaluaciones->keyBy('numero_examen');

        return [
            'id_inscripcion' => $insc->id_inscripcion,
            'codigo_tramite' => $insc->postulante?->codigo_tramite,
            'postulante' => $usuario ? [
                'ci' => $usuario->ci,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
            ] : null,
            'gestion' => $insc->convocatoria?->gestion?->nombre,
            'convocatoria' => $insc->convocatoria?->nombre,
            'notas' => [
                1 => $notas->get(1)?->nota !== null ? (float) $notas->get(1)->nota : null,
                2 => $notas->get(2)?->nota !== null ? (float) $notas->get(2)->nota : null,
                3 => $notas->get(3)?->nota !== null ? (float) $notas->get(3)->nota : null,
            ],
            'promedio_final' => $insc->promedio_final !== null ? (float) $insc->promedio_final : null,
            'estado_academico' => $insc->estado_academico,
            'carrera_admitida' => $insc->carreraAdmitida?->nombre,
        ];
    }
}
