<?php

namespace App\Modules\Administrative\Services;

use App\Modules\Academics\Models\Grupo;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\Pago;
use App\Modules\Registration\Models\Postulante;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CU13 — Dashboard administrativo: KPIs y gráficas del proceso de admisión.
 * Las agregaciones pesadas se cachean (60s) para no degradar la BD.
 */
class DashboardService
{
    private const TTL = 60;

    public function metricas(?int $idConvocatoria): array
    {
        $clave = 'dashboard:'.($idConvocatoria ?? 'all');

        return Cache::remember($clave, self::TTL, fn () => $this->calcular($idConvocatoria));
    }

    private function calcular(?int $idConvocatoria): array
    {
        $porEstado = Inscripcion::query()
            ->when($idConvocatoria, fn ($q, $id) => $q->where('id_convocatoria', $id))
            ->selectRaw('estado_academico, COUNT(*) AS c')
            ->groupBy('estado_academico')
            ->pluck('c', 'estado_academico');

        $cuenta = fn (string $e) => (int) ($porEstado[$e] ?? 0);

        $total = (int) $porEstado->sum();
        $aprobados = $cuenta('APROBADO') + $cuenta('ADMITIDO') + $cuenta('APROBADO_SIN_CUPO');
        $reprobados = $cuenta('REPROBADO');
        $conResultado = $aprobados + $reprobados;

        // Habilitados = pagaron (todo menos PENDIENTE). Ausentes = no rindieron los 3.
        $habilitados = $total - $cuenta('PENDIENTE');
        $ausentes = max(0, $habilitados - $conResultado);

        $recaudado = (float) Pago::where('estado', Pago::ESTADO_APROBADO)
            ->when($idConvocatoria, fn ($q, $id) => $q->whereHas('inscripcion', fn ($i) => $i->where('id_convocatoria', $id)))
            ->sum('monto');

        return [
            'kpis' => [
                'total_inscritos' => $total,
                'total_aprobados' => $aprobados,
                'total_reprobados' => $reprobados,
                'total_grupos_habilitados' => (int) Grupo::when($idConvocatoria, fn ($q, $id) => $q->where('id_convocatoria', $id))->count(),
                'porcentaje_aprobacion' => $conResultado > 0 ? round($aprobados / $conResultado * 100, 1) : 0.0,
                'tasa_ausentismo' => $habilitados > 0 ? round($ausentes / $habilitados * 100, 1) : 0.0,
                'recaudado_bs' => $recaudado,
            ],
            'por_estado' => [
                'PENDIENTE' => $cuenta('PENDIENTE'),
                'ELEGIBLE' => $cuenta('ELEGIBLE'),
                'APROBADO' => $cuenta('APROBADO'),
                'ADMITIDO' => $cuenta('ADMITIDO'),
                'APROBADO_SIN_CUPO' => $cuenta('APROBADO_SIN_CUPO'),
                'REPROBADO' => $reprobados,
            ],
            'carreras_solicitadas' => $this->carrerasSolicitadas($idConvocatoria),
            'procedencia' => $this->procedencia($idConvocatoria),
        ];
    }

    /** Carreras más solicitadas (1ª opción). */
    private function carrerasSolicitadas(?int $idConvocatoria): array
    {
        return DB::table('carrera_inscripcion as ci')
            ->join('inscripcion as i', 'i.id_inscripcion', '=', 'ci.id_inscripcion')
            ->join('carrera as c', 'c.id_carrera', '=', 'ci.id_carrera')
            ->where('ci.orden', 1)
            ->when($idConvocatoria, fn ($q, $id) => $q->where('i.id_convocatoria', $id))
            ->selectRaw('c.nombre AS etiqueta, COUNT(*) AS total')
            ->groupBy('c.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['etiqueta' => $r->etiqueta, 'total' => (int) $r->total])
            ->all();
    }

    /** Distribución por procedencia (ciudad) de los postulantes. */
    private function procedencia(?int $idConvocatoria): array
    {
        return Postulante::query()
            ->when($idConvocatoria, fn ($q, $id) => $q->whereHas('inscripciones', fn ($i) => $i->where('id_convocatoria', $id)))
            ->whereNotNull('procedencia')
            ->where('procedencia', '!=', '')
            ->selectRaw('procedencia AS etiqueta, COUNT(*) AS total')
            ->groupBy('procedencia')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['etiqueta' => $r->etiqueta, 'total' => (int) $r->total])
            ->all();
    }
}
