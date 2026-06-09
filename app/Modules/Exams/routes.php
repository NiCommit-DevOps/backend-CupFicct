<?php

use App\Modules\Exams\Http\Controllers\CarreraController;
use App\Modules\Exams\Http\Controllers\CorteAdmisionController;
use App\Modules\Exams\Http\Controllers\ExamenAlumnoController;
use App\Modules\Exams\Http\Controllers\HistorialAcademicoController;
use App\Modules\Exams\Http\Controllers\HorarioController;
use App\Modules\Exams\Http\Controllers\MateriaController;
use App\Modules\Exams\Http\Controllers\ReporteController;
use App\Modules\Exams\Http\Controllers\ResultadoExamenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Exams (Modulo_Examenes) — rutas (prefijo api/v1)
| CU08 — Cupos por Carrera · CU06 — Notas por materia (carga manual)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::get('carreras', [CarreraController::class, 'index'])->middleware('permiso:carreras.index');
    Route::get('carreras/catalogos', [CarreraController::class, 'catalogos'])->middleware('permiso:carreras.index');
    Route::get('carreras/{carrera}', [CarreraController::class, 'show'])->middleware('permiso:carreras.index');
    Route::post('carreras', [CarreraController::class, 'store'])->middleware('permiso:carreras.store');
    Route::put('carreras/{carrera}', [CarreraController::class, 'update'])->middleware('permiso:carreras.update');
    Route::delete('carreras/{carrera}', [CarreraController::class, 'destroy'])->middleware('permiso:carreras.destroy');

    // CU06/CU10 — Catálogo de materias.
    Route::get('materias', [MateriaController::class, 'index'])->middleware('permiso:materias.index');
    Route::post('materias', [MateriaController::class, 'store'])->middleware('permiso:materias.store');
    Route::put('materias/{materia}', [MateriaController::class, 'update'])->middleware('permiso:materias.update');
    Route::delete('materias/{materia}', [MateriaController::class, 'destroy'])->middleware('permiso:materias.destroy');

    // CU06/CU10 — Horario de clases: general (staff, en Materias) y personal
    // (docente: sus grupos / postulante: su boleta de horario).
    Route::get('horarios', [HorarioController::class, 'index'])->middleware('permiso:materias.index');
    Route::get('mi-horario', [HorarioController::class, 'miHorario'])->middleware('permiso:horario.index');

    // CU16 — Historial académico (solo lectura, gestiones concluidas).
    Route::get('historial', [HistorialAcademicoController::class, 'buscar'])->middleware('permiso:historial.index');

    // CU12 — Reportes oficiales (acta, padrón, certificados) + descargas CSV.
    Route::middleware('permiso:reportes.index')->group(function () {
        Route::get('reportes/acta', [ReporteController::class, 'acta']);
        Route::get('reportes/acta/csv', [ReporteController::class, 'actaCsv']);
        Route::get('reportes/padron', [ReporteController::class, 'padron']);
        Route::get('reportes/padron/csv', [ReporteController::class, 'padronCsv']);
        Route::get('reportes/certificados', [ReporteController::class, 'certificados']);
        // Reportes obligatorios adicionales (lista/aprobados/reprobados, estadísticas, docentes por grupo).
        Route::get('reportes/lista', [ReporteController::class, 'lista']);
        Route::get('reportes/estadisticas', [ReporteController::class, 'estadisticas']);
        Route::get('reportes/docentes-grupos', [ReporteController::class, 'docentesPorGrupo']);
    });

    // CU07 — Corte de admisión por cupos.
    Route::get('convocatorias/{convocatoria}/corte', [CorteAdmisionController::class, 'estado'])->middleware('permiso:admision.index');
    Route::post('convocatorias/{convocatoria}/corte', [CorteAdmisionController::class, 'ejecutar'])->middleware('permiso:admision.ejecutar');

    // CU06 — Carga manual de notas por materia (staff): ver y guardar.
    Route::get('examenes/resultados-staff', [ResultadoExamenController::class, 'index'])->middleware('permiso:notas.index');
    Route::put('examenes/inscripciones/{inscripcion}/notas', [ResultadoExamenController::class, 'guardarNotas'])->middleware('permiso:notas.update');
    // CU06 — Carga masiva de notas por materia desde archivo (Excel/CSV).
    Route::post('examenes/notas/importar', [ResultadoExamenController::class, 'importar'])->middleware('permiso:notas.update');

    // CU06 — Consulta de exámenes del postulante (solo lectura).
    Route::get('examenes/resultados', [ExamenAlumnoController::class, 'misResultados'])->middleware('permiso:examenes.rendir');
});
