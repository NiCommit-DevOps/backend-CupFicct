<?php

use App\Modules\Academics\Http\Controllers\AulaController;
use App\Modules\Academics\Http\Controllers\DocenteController;
use App\Modules\Academics\Http\Controllers\GrupoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Academics (Modulo_Grupos) — rutas (prefijo api/v1)
| CU17 — Gestionar Aulas y Recursos Físicos
| CU10 — Gestionar Docentes y Horarios
| CU09 — Gestionar Asignación de Grupos
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    // CU17 — Aulas
    Route::get('aulas', [AulaController::class, 'index'])->middleware('permiso:aulas.index');
    Route::get('aulas/{aula}', [AulaController::class, 'show'])->middleware('permiso:aulas.index');
    Route::post('aulas', [AulaController::class, 'store'])->middleware('permiso:aulas.store');
    Route::put('aulas/{aula}', [AulaController::class, 'update'])->middleware('permiso:aulas.update');
    Route::delete('aulas/{aula}', [AulaController::class, 'destroy'])->middleware('permiso:aulas.destroy');

    // CU10 — Docentes y horarios
    Route::get('docentes', [DocenteController::class, 'index'])->middleware('permiso:docentes.index');
    Route::get('docentes/catalogos', [DocenteController::class, 'catalogos'])->middleware('permiso:docentes.index');
    Route::get('docentes/{docente}', [DocenteController::class, 'show'])->middleware('permiso:docentes.index');
    Route::post('docentes', [DocenteController::class, 'store'])->middleware('permiso:docentes.store');
    Route::put('docentes/{docente}', [DocenteController::class, 'update'])->middleware('permiso:docentes.update');
    Route::delete('docentes/{docente}', [DocenteController::class, 'destroy'])->middleware('permiso:docentes.destroy');

    // CU09 — Grupos de examen y asignación de postulantes
    Route::get('grupos', [GrupoController::class, 'index'])->middleware('permiso:grupos.index');
    Route::get('grupos/catalogos', [GrupoController::class, 'catalogos'])->middleware('permiso:grupos.store');
    Route::get('grupos/asignacion', [GrupoController::class, 'asignacion'])->middleware('permiso:grupos.index');
    Route::get('grupos/{grupo}', [GrupoController::class, 'show'])->middleware('permiso:grupos.index');
    Route::post('grupos', [GrupoController::class, 'store'])->middleware('permiso:grupos.store');
    Route::put('grupos/{grupo}', [GrupoController::class, 'update'])->middleware('permiso:grupos.update');
    Route::delete('grupos/{grupo}', [GrupoController::class, 'destroy'])->middleware('permiso:grupos.destroy');

    // CU09 — Creación automática de grupos (techo(inscritos/70)) + asignación.
    Route::post('grupos/crear-automatico', [GrupoController::class, 'crearAutomatico'])->middleware('permiso:grupos.store');

    // CU09 — Asignación de postulantes a grupos
    Route::post('grupos/asignar', [GrupoController::class, 'asignar'])->middleware('permiso:grupos.asignar');
    Route::post('grupos/desasignar', [GrupoController::class, 'desasignar'])->middleware('permiso:grupos.asignar');
    Route::post('grupos/asignar-lote', [GrupoController::class, 'asignarLote'])->middleware('permiso:grupos.asignar');
    Route::post('grupos/rebalancear', [GrupoController::class, 'rebalancear'])->middleware('permiso:grupos.asignar');
});
