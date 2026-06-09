<?php

use App\Modules\Administrative\Http\Controllers\BitacoraController;
use App\Modules\Administrative\Http\Controllers\ConvocatoriaController;
use App\Modules\Administrative\Http\Controllers\DashboardController;
use App\Modules\Administrative\Http\Controllers\GestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Administrative — rutas (prefijo api/v1)
| CU19 — Gestionar Convocatoria · CU11 — Gestionar Bitácora
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    // CU13 — Dashboard administrativo (KPIs y gráficas).
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permiso:dashboard.index');

    // CU11 — Bitácora: visor de auditoría de SOLO lectura (sin store/update/destroy).
    Route::get('bitacora/tablas', [BitacoraController::class, 'tablas'])->middleware('permiso:bitacora.index');
    Route::get('bitacora', [BitacoraController::class, 'index'])->middleware('permiso:bitacora.index');
    Route::get('bitacora/{bitacora}', [BitacoraController::class, 'show'])->middleware('permiso:bitacora.index');

    // CU19 — Gestiones (ancla temporal)
    Route::get('gestiones', [GestionController::class, 'index'])->middleware('permiso:gestiones.index');
    Route::get('gestiones/{gestion}', [GestionController::class, 'show'])->middleware('permiso:gestiones.index');
    Route::post('gestiones', [GestionController::class, 'store'])->middleware('permiso:gestiones.store');
    Route::put('gestiones/{gestion}', [GestionController::class, 'update'])->middleware('permiso:gestiones.update');
    Route::patch('gestiones/{gestion}/estado', [GestionController::class, 'cambiarEstado'])->middleware('permiso:gestiones.update');
    Route::delete('gestiones/{gestion}', [GestionController::class, 'destroy'])->middleware('permiso:gestiones.destroy');

    // CU19 — Convocatorias
    Route::get('convocatorias', [ConvocatoriaController::class, 'index'])->middleware('permiso:convocatorias.index');
    // CU08/CU19 — Cupos por carrera de la convocatoria (antes del comodín {convocatoria}).
    Route::get('convocatorias/{convocatoria}/cupos', [ConvocatoriaController::class, 'cupos'])->middleware('permiso:convocatorias.index');
    Route::put('convocatorias/{convocatoria}/cupos', [ConvocatoriaController::class, 'guardarCupos'])->middleware('permiso:convocatorias.update');
    Route::get('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'show'])->middleware('permiso:convocatorias.index');
    Route::post('convocatorias', [ConvocatoriaController::class, 'store'])->middleware('permiso:convocatorias.store');
    Route::put('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'update'])->middleware('permiso:convocatorias.update');
    Route::patch('convocatorias/{convocatoria}/estado', [ConvocatoriaController::class, 'cambiarEstado'])->middleware('permiso:convocatorias.update');
    Route::delete('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'destroy'])->middleware('permiso:convocatorias.destroy');
});
