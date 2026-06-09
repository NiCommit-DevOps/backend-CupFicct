<?php

namespace App\Modules\Exams\Services;

use App\Modules\Academics\Models\Grupo;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Support\Facades\DB;

/**
 * CU12 — Generación de reportes oficiales (acta de admitidos, padrón, certificados).
 * Solo lectura: no altera estados.
 */
class ReporteService
{
    /** Acta oficial de admitidos, agrupada y ordenada por carrera. */
    public function actaAdmitidos(int $idConvocatoria): array
    {
        $convocatoria = $this->convocatoria($idConvocatoria);

        $admitidos = Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->where('estado_academico', Inscripcion::ESTADO_ADMITIDO)
            ->with(['postulante.usuario', 'carreraAdmitida'])
            ->orderByDesc('promedio_final')
            ->get();

        $porCarrera = $admitidos
            ->groupBy(fn (Inscripcion $i) => $i->carreraAdmitida?->nombre ?? 'Sin carrera')
            ->map(fn ($grupo, $carrera) => [
                'carrera' => $carrera,
                'admitidos' => $grupo->map(fn (Inscripcion $i) => [
                    'codigo_tramite' => $i->postulante?->codigo_tramite,
                    'ci' => $i->postulante?->usuario?->ci,
                    'nombres' => $i->postulante?->usuario?->nombres,
                    'apellidos' => $i->postulante?->usuario?->apellidos,
                    'promedio_final' => $i->promedio_final !== null ? (float) $i->promedio_final : null,
                ])->values(),
            ])
            ->sortKeys()
            ->values();

        return [
            'convocatoria' => $convocatoria->nombre,
            'gestion' => $convocatoria->gestion?->nombre,
            'total' => $admitidos->count(),
            'por_carrera' => $porCarrera,
        ];
    }

    /** Padrón académico: todos los inscritos con sus notas consolidadas. */
    public function padron(int $idConvocatoria): array
    {
        $convocatoria = $this->convocatoria($idConvocatoria);

        $inscripciones = Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->with(['postulante.usuario', 'carreras', 'carreraAdmitida', 'evaluaciones'])
            ->orderByDesc('promedio_final')
            ->orderBy('id_inscripcion')
            ->get();

        return [
            'convocatoria' => $convocatoria->nombre,
            'gestion' => $convocatoria->gestion?->nombre,
            'total' => $inscripciones->count(),
            'filas' => $inscripciones->map(fn (Inscripcion $i) => $this->filaCompleta($i))->values(),
        ];
    }

    /**
     * Certificados de calificación que coinciden con el CI o código de trámite
     * (en cualquier convocatoria, para reclamos/impugnaciones).
     */
    public function certificados(string $termino): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        return Inscripcion::query()
            ->where(function ($w) use ($termino) {
                $w->whereHas('postulante.usuario', fn ($u) => $u->where('ci', 'ILIKE', $termino));
                if (ctype_digit($termino)) {
                    $w->orWhereHas('postulante', fn ($p) => $p->where('codigo_tramite', (int) $termino));
                }
            })
            ->with(['postulante.usuario', 'convocatoria.gestion', 'carreras', 'carreraAdmitida', 'evaluaciones'])
            ->orderByDesc('id_inscripcion')
            ->get()
            ->map(fn (Inscripcion $i) => $this->filaCompleta($i, true))
            ->values()
            ->all();
    }

    /**
     * Lista de postulantes filtrada por resultado: 'todos', 'aprobados' o
     * 'reprobados'. Cubre los reportes obligatorios de lista general, aprobados
     * y reprobados.
     */
    public function lista(int $idConvocatoria, string $filtro = 'todos'): array
    {
        $convocatoria = $this->convocatoria($idConvocatoria);

        $aprobados = [Inscripcion::ESTADO_APROBADO, Inscripcion::ESTADO_ADMITIDO, Inscripcion::ESTADO_APROBADO_SIN_CUPO];

        $inscripciones = Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->when($filtro === 'aprobados', fn ($q) => $q->whereIn('estado_academico', $aprobados))
            ->when($filtro === 'reprobados', fn ($q) => $q->where('estado_academico', Inscripcion::ESTADO_REPROBADO))
            ->with(['postulante.usuario', 'carreras', 'carreraAdmitida', 'evaluaciones'])
            ->orderByDesc('promedio_final')
            ->orderBy('id_inscripcion')
            ->get();

        return [
            'convocatoria' => $convocatoria->nombre,
            'gestion' => $convocatoria->gestion?->nombre,
            'filtro' => $filtro,
            'total' => $inscripciones->count(),
            'filas' => $inscripciones->map(fn (Inscripcion $i) => $this->filaCompleta($i))->values(),
        ];
    }

    /**
     * Estadísticas del proceso: promedios generales, rendimiento por materia y
     * grupos con mayor cantidad de aprobados.
     */
    public function estadisticas(int $idConvocatoria): array
    {
        $convocatoria = $this->convocatoria($idConvocatoria);

        return [
            'convocatoria' => $convocatoria->nombre,
            'gestion' => $convocatoria->gestion?->nombre,
            'grupos_habilitados' => $this->gruposHabilitados($idConvocatoria),
            'promedios_generales' => $this->promediosGenerales($idConvocatoria),
            'por_materia' => $this->estadisticasPorMateria($idConvocatoria),
            'grupos_top_aprobados' => $this->gruposPorAprobados($idConvocatoria),
        ];
    }

    /**
     * Cantidad de grupos habilitados (reporte obligatorio): total y desglose por
     * turno, de la convocatoria del reporte.
     *
     * @return array{total:int, manana:int, tarde:int}
     */
    private function gruposHabilitados(int $idConvocatoria): array
    {
        $grupos = Grupo::where('id_convocatoria', $idConvocatoria)->get(['id_grupo', 'turno']);

        return [
            'total' => $grupos->count(),
            'manana' => $grupos->filter(fn (Grupo $g) => Grupo::turnoNormalizado($g->turno) === 'MAÑANA')->count(),
            'tarde' => $grupos->filter(fn (Grupo $g) => Grupo::turnoNormalizado($g->turno) === 'TARDE')->count(),
        ];
    }

    /**
     * Reporte "Docentes por grupos": cada grupo (de la convocatoria activa) con
     * sus docentes asignados, el cupo ocupado y la cantidad de aprobados.
     */
    public function docentesPorGrupo(): array
    {
        $idConvocatoria = Convocatoria::activa()?->id_convocatoria;
        if (! $idConvocatoria) {
            return [];
        }

        $aprobados = [Inscripcion::ESTADO_APROBADO, Inscripcion::ESTADO_ADMITIDO, Inscripcion::ESTADO_APROBADO_SIN_CUPO];

        return Grupo::query()
            ->where('id_convocatoria', $idConvocatoria)
            ->with(['docentes.usuario'])
            ->orderBy('sigla')
            ->get()
            ->map(function (Grupo $g) use ($aprobados) {
                $inscritos = Inscripcion::where('id_grupo', $g->id_grupo)->count();
                $aprob = Inscripcion::where('id_grupo', $g->id_grupo)
                    ->whereIn('estado_academico', $aprobados)->count();

                return [
                    'id_grupo' => $g->id_grupo,
                    'sigla' => $g->sigla,
                    'nombre' => $g->nombre,
                    'turno' => $g->turno,
                    'inscritos' => $inscritos,
                    'aprobados' => $aprob,
                    'docentes' => $g->docentes->map(fn ($d) => [
                        'id_docente' => $d->id_docente,
                        'nombre' => trim(($d->usuario?->nombres ?? '').' '.($d->usuario?->apellidos ?? '')),
                        'profesion' => $d->profesion,
                    ])->values(),
                ];
            })
            ->values()
            ->all();
    }

    /* ===================== Internos ===================== */

    /** Conteos y promedio general de los postulantes que rindieron. */
    private function promediosGenerales(int $idConvocatoria): array
    {
        $aprobados = [Inscripcion::ESTADO_APROBADO, Inscripcion::ESTADO_ADMITIDO, Inscripcion::ESTADO_APROBADO_SIN_CUPO];

        $base = Inscripcion::where('id_convocatoria', $idConvocatoria)->whereNotNull('promedio_final');

        return [
            'total_con_nota' => (clone $base)->count(),
            'promedio_general' => round((float) (clone $base)->avg('promedio_final'), 2),
            'promedio_maximo' => round((float) (clone $base)->max('promedio_final'), 2),
            'promedio_minimo' => round((float) (clone $base)->min('promedio_final'), 2),
            'aprobados' => (clone $base)->whereIn('estado_academico', $aprobados)->count(),
            'reprobados' => (clone $base)->where('estado_academico', Inscripcion::ESTADO_REPROBADO)->count(),
        ];
    }

    /**
     * Rendimiento por materia (Computación, Matemáticas, Inglés, Física): total
     * de respuestas, correctas y porcentaje de acierto en la convocatoria.
     */
    private function estadisticasPorMateria(int $idConvocatoria): array
    {
        $umbral = Inscripcion::NOTA_APROBACION; // 60

        return DB::table('nota_materia as nm')
            ->join('evaluacion as e', 'e.id_evaluacion', '=', 'nm.id_evaluacion')
            ->join('inscripcion as i', 'i.id_inscripcion', '=', 'e.id_inscripcion')
            ->join('materia as m', 'm.id_materia', '=', 'nm.id_materia')
            ->where('i.id_convocatoria', $idConvocatoria)
            ->selectRaw(
                'm.nombre AS materia, COUNT(*) AS registradas, '
                .'AVG(nm.nota) AS promedio, '
                .'SUM(CASE WHEN nm.nota >= ? THEN 1 ELSE 0 END) AS aprobadas',
                [$umbral],
            )
            ->groupBy('m.nombre')
            ->orderBy('m.nombre')
            ->get()
            ->map(function ($r) {
                $registradas = (int) $r->registradas;
                $aprobadas = (int) $r->aprobadas;

                return [
                    'materia' => $r->materia,
                    'registradas' => $registradas,
                    'promedio' => round((float) $r->promedio, 2),
                    'aprobadas' => $aprobadas,
                    'porcentaje_aprobacion' => $registradas > 0 ? round($aprobadas / $registradas * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    /** Grupos ordenados por cantidad de aprobados (descendente). */
    private function gruposPorAprobados(int $idConvocatoria): array
    {
        $aprobados = [Inscripcion::ESTADO_APROBADO, Inscripcion::ESTADO_ADMITIDO, Inscripcion::ESTADO_APROBADO_SIN_CUPO];

        return Grupo::query()
            ->where('id_convocatoria', $idConvocatoria)
            ->orderBy('sigla')
            ->get()
            ->map(function (Grupo $g) use ($idConvocatoria, $aprobados) {
                $base = Inscripcion::where('id_grupo', $g->id_grupo)->where('id_convocatoria', $idConvocatoria);

                return [
                    'id_grupo' => $g->id_grupo,
                    'sigla' => $g->sigla,
                    'nombre' => $g->nombre,
                    'inscritos' => (clone $base)->count(),
                    'aprobados' => (clone $base)->whereIn('estado_academico', $aprobados)->count(),
                ];
            })
            ->sortByDesc('aprobados')
            ->values()
            ->all();
    }

    private function filaCompleta(Inscripcion $i, bool $conContexto = false): array
    {
        $u = $i->postulante?->usuario;
        // Nota de cada examen = promedio de sus materias (carga manual del staff).
        $notas = $i->evaluaciones->keyBy('numero_examen');
        $carreras = $i->carreras->sortBy(fn ($c) => $c->pivot->orden ?? 1)->values();

        $fila = [
            'id_inscripcion' => $i->id_inscripcion,
            'codigo_tramite' => $i->postulante?->codigo_tramite,
            'ci' => $u?->ci,
            'nombres' => $u?->nombres,
            'apellidos' => $u?->apellidos,
            'carrera_1' => $carreras->get(0)?->nombre,
            'carrera_2' => $carreras->get(1)?->nombre,
            'notas' => [
                1 => $notas->get(1)?->nota !== null ? (float) $notas->get(1)->nota : null,
                2 => $notas->get(2)?->nota !== null ? (float) $notas->get(2)->nota : null,
                3 => $notas->get(3)?->nota !== null ? (float) $notas->get(3)->nota : null,
            ],
            'promedio_final' => $i->promedio_final !== null ? (float) $i->promedio_final : null,
            'estado_academico' => $i->estado_academico,
            'carrera_admitida' => $i->carreraAdmitida?->nombre,
        ];

        if ($conContexto) {
            $fila['gestion'] = $i->convocatoria?->gestion?->nombre;
            $fila['convocatoria'] = $i->convocatoria?->nombre;
        }

        return $fila;
    }

    private function convocatoria(int $id): Convocatoria
    {
        return Convocatoria::with('gestion')->find($id) ?? abort(404, 'Convocatoria no encontrada.');
    }
}
