<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU12 — Generar Reportes (acta de admitidos, padrón, certificados).
 */
class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function acta(Request $request): JsonResponse
    {
        return response()->json($this->reportes->actaAdmitidos($this->convocatoria($request)));
    }

    public function actaCsv(Request $request): StreamedResponse
    {
        $acta = $this->reportes->actaAdmitidos($this->convocatoria($request));

        $filas = [];
        foreach ($acta['por_carrera'] as $grupo) {
            foreach ($grupo['admitidos'] as $a) {
                $filas[] = [$grupo['carrera'], $a['codigo_tramite'], $a['ci'], $a['apellidos'], $a['nombres'], $a['promedio_final'], $a['preferencia']];
            }
        }

        return $this->csv('acta_admitidos.csv',
            ['Carrera', 'Código', 'CI', 'Apellidos', 'Nombres', 'Promedio', 'Preferencia'],
            $filas,
        );
    }

    public function padron(Request $request): JsonResponse
    {
        return response()->json($this->reportes->padron($this->convocatoria($request)));
    }

    public function padronCsv(Request $request): StreamedResponse
    {
        $padron = $this->reportes->padron($this->convocatoria($request));

        $filas = array_map(fn ($f) => [
            $f['codigo_tramite'], $f['ci'], $f['apellidos'], $f['nombres'],
            $f['carrera_1'], $f['carrera_2'],
            $f['notas'][1], $f['notas'][2], $f['notas'][3],
            $f['promedio_final'], $f['estado_academico'], $f['carrera_admitida'],
        ], $padron['filas']);

        return $this->csv('padron_academico.csv',
            ['Código', 'CI', 'Apellidos', 'Nombres', 'Carrera 1', 'Carrera 2', 'Nota 1', 'Nota 2', 'Nota 3', 'Promedio', 'Estado', 'Carrera admitida'],
            $filas,
        );
    }

    public function certificados(Request $request): JsonResponse
    {
        $termino = $request->string('buscar')->toString();

        return response()->json(['data' => $this->reportes->certificados($termino)]);
    }

    /** Lista general / aprobados / reprobados según el filtro. */
    public function lista(Request $request): JsonResponse
    {
        return response()->json($this->reportes->lista($this->convocatoria($request), $this->filtroLista($request)));
    }

    public function listaCsv(Request $request): StreamedResponse
    {
        $lista = $this->reportes->lista($this->convocatoria($request), $this->filtroLista($request));

        $filas = array_map(fn ($f) => [
            $f['codigo_tramite'], $f['ci'], $f['apellidos'], $f['nombres'],
            $f['carrera_1'], $f['promedio_final'], $f['estado_academico'],
        ], $lista['filas']);

        return $this->csv('lista_postulantes.csv',
            ['Código', 'CI', 'Apellidos', 'Nombres', 'Carrera 1', 'Promedio', 'Estado'],
            $filas,
        );
    }

    /** Promedios generales, estadísticas por materia y grupos con más aprobados. */
    public function estadisticas(Request $request): JsonResponse
    {
        return response()->json($this->reportes->estadisticas($this->convocatoria($request)));
    }

    public function estadisticasCsv(Request $request): StreamedResponse
    {
        $e = $this->reportes->estadisticas($this->convocatoria($request));
        $gh = $e['grupos_habilitados'];
        $pg = $e['promedios_generales'];

        $filas = [
            ['Estadísticas', $e['gestion'] ?? '', $e['convocatoria']],
            [],
            ['Grupos habilitados', 'Total', 'Mañana', 'Tarde'],
            ['', $gh['total'], $gh['manana'], $gh['tarde']],
            [],
            ['Promedios generales'],
            ['Con nota', $pg['total_con_nota']],
            ['Promedio general', $pg['promedio_general']],
            ['Promedio máximo', $pg['promedio_maximo']],
            ['Promedio mínimo', $pg['promedio_minimo']],
            ['Aprobados', $pg['aprobados']],
            ['Reprobados', $pg['reprobados']],
            [],
            ['Por materia', 'Notas registradas', 'Promedio', 'Aprobadas', '% aprobación'],
        ];
        foreach ($e['por_materia'] as $m) {
            $filas[] = [$m['materia'], $m['registradas'], $m['promedio'], $m['aprobadas'], $m['porcentaje_aprobacion']];
        }
        $filas[] = [];
        $filas[] = ['Grupos por aprobados', 'Inscritos', 'Aprobados'];
        foreach ($e['grupos_top_aprobados'] as $g) {
            $filas[] = [$g['sigla'].' · '.$g['nombre'], $g['inscritos'], $g['aprobados']];
        }

        return $this->csvFilas('estadisticas.csv', $filas);
    }

    /** Docentes por grupos (cupo, aprobados, %) + ranking de docentes por % aprobados. */
    public function docentesPorGrupo(): JsonResponse
    {
        return response()->json(['data' => $this->reportes->docentesPorGrupo()]);
    }

    public function docentesCsv(): StreamedResponse
    {
        $ranking = $this->reportes->docentesPorGrupo()['ranking'];

        $filas = [];
        $pos = 1;
        foreach ($ranking as $d) {
            $filas[] = [$pos++, $d['nombre'], $d['profesion'], $d['grupos'], $d['inscritos'], $d['aprobados'], $d['porcentaje']];
        }

        return $this->csv('docentes_ranking.csv',
            ['#', 'Docente', 'Profesión', 'Grupos', 'Inscritos', 'Aprobados', '% aprobados'],
            $filas,
        );
    }

    /** Rendimiento académico comparado entre gestiones. */
    public function comparativaGestiones(): JsonResponse
    {
        return response()->json(['data' => $this->reportes->comparativaGestiones()]);
    }

    public function comparativaGestionesCsv(): StreamedResponse
    {
        $filas = array_map(fn ($g) => [
            $g['gestion'], $g['total_inscritos'], $g['con_nota'], $g['aprobados'],
            $g['reprobados'], $g['admitidos'], $g['promedio_general'], $g['porcentaje_aprobacion'],
        ], $this->reportes->comparativaGestiones());

        return $this->csv('comparativa_gestiones.csv',
            ['Gestión', 'Inscritos', 'Con nota', 'Aprobados', 'Reprobados', 'Admitidos', 'Promedio general', '% aprobación'],
            $filas,
        );
    }

    /* ===================== Internos ===================== */

    private function convocatoria(Request $request): int
    {
        $id = $request->integer('id_convocatoria');
        abort_if($id <= 0, 422, 'Indica la convocatoria del reporte.');

        return $id;
    }

    /** Normaliza el filtro de la lista de postulantes. */
    private function filtroLista(Request $request): string
    {
        $filtro = $request->string('filtro')->toString() ?: 'todos';

        return in_array($filtro, ['todos', 'aprobados', 'reprobados'], true) ? $filtro : 'todos';
    }

    /** Genera una descarga CSV (UTF-8 con BOM para Excel). */
    private function csv(string $nombre, array $encabezados, array $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($salida, $encabezados);
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * CSV de filas heterogéneas (sin encabezado fijo), para reportes con varias
     * secciones como las estadísticas.
     *
     * @param  array<int,array<int,mixed>>  $filas
     */
    private function csvFilas(string $nombre, array $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM UTF-8
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
