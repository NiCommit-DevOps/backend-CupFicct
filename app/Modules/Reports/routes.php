<?php

use App\Modules\Reports\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Reports (Reportes) — rutas (prefijo api/v1)
| CU12 — Generación de reportes oficiales + estadísticos, en vista dinámica
| (web) y formatos estáticos (PDF vía impresión / CSV).
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::middleware('permiso:reportes.index')->group(function () {
        // Reportes oficiales (acta, padrón, certificados) + descargas CSV.
        Route::get('reportes/acta', [ReporteController::class, 'acta']);
        Route::get('reportes/acta/csv', [ReporteController::class, 'actaCsv']);
        Route::get('reportes/padron', [ReporteController::class, 'padron']);
        Route::get('reportes/padron/csv', [ReporteController::class, 'padronCsv']);
        Route::get('reportes/certificados', [ReporteController::class, 'certificados']);

        // Reportes obligatorios adicionales (lista/aprobados/reprobados, estadísticas, docentes por grupo).
        Route::get('reportes/lista', [ReporteController::class, 'lista']);
        Route::get('reportes/lista/csv', [ReporteController::class, 'listaCsv']);
        Route::get('reportes/estadisticas', [ReporteController::class, 'estadisticas']);
        Route::get('reportes/estadisticas/csv', [ReporteController::class, 'estadisticasCsv']);
        Route::get('reportes/docentes-grupos', [ReporteController::class, 'docentesPorGrupo']);
        Route::get('reportes/docentes-grupos/csv', [ReporteController::class, 'docentesCsv']);

        // Rendimiento académico comparado entre gestiones.
        Route::get('reportes/comparativa-gestiones', [ReporteController::class, 'comparativaGestiones']);
        Route::get('reportes/comparativa-gestiones/csv', [ReporteController::class, 'comparativaGestionesCsv']);
    });
});
