<?php

use App\Modules\Registration\Http\Controllers\PagoController;
use App\Modules\Registration\Http\Controllers\PostulacionPublicaController;
use App\Modules\Registration\Http\Controllers\PostulanteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Registration (Modulo_Registro) — rutas (prefijo api/v1)
| CU04 — Gestionar Postulantes
|--------------------------------------------------------------------------
| Dos vías de inscripción:
|  - Pública (landing): el postulante consulta la convocatoria abierta y envía
|    su solicitud (PENDIENTE, sin cuenta funcional ni pago). Sin autenticación.
|  - Interna (staff): el Administrador da de alta postulantes (espejo de CU10).
*/

// Auto-registro público (sin autenticación) — landing FICCT.
Route::get('public/postulacion/convocatoria', [PostulacionPublicaController::class, 'convocatoria']);
Route::post('public/postulacion', [PostulacionPublicaController::class, 'store']);

/*
| CU05 — Procesar Pago de Inscripción (PayPal).
| Flujo público: una persona externa (postulante ya registrado) consulta su
| deuda por carnet, paga con el botón de PayPal y, al confirmarse, el sistema
| habilita su inscripción. El historial vive en la zona autenticada (abajo).
*/
Route::prefix('public/pagos')->group(function () {
    Route::get('config', [PagoController::class, 'config']);
    Route::post('buscar', [PagoController::class, 'buscar']);
    Route::post('orden', [PagoController::class, 'crearOrden']);
    Route::post('capturar', [PagoController::class, 'capturar']);
});

Route::middleware('auth:api')->group(function () {
    // Historial de pagos: propio para el postulante, global para el staff con permiso.
    Route::get('pagos', [PagoController::class, 'index']);
    // CU15 — Reporte de conciliación de caja y recaudación (fiscalización, solo staff).
    Route::get('pagos/reportes', [PagoController::class, 'reportes'])->middleware('permiso:pagos.reportes');

    // Catálogos para el formulario de alta (convocatoria vigente, carreras, unidades, turnos).
    Route::get('postulantes/catalogos', [PostulanteController::class, 'catalogos'])->middleware('permiso:postulantes.store');

    // CU14 — Carga masiva (debe ir antes de la ruta con {postulante}).
    Route::post('postulantes/importar', [PostulanteController::class, 'importar'])->middleware('permiso:postulantes.store');

    // CU04 — Aprobación masiva de postulantes pendientes.
    Route::post('postulantes/aprobar-todos', [PostulanteController::class, 'aprobarTodos'])->middleware('permiso:postulantes.update');

    // Consulta / gestión del padrón.
    Route::get('postulantes', [PostulanteController::class, 'index'])->middleware('permiso:postulantes.index');
    // Documento del título de bachiller (antes de la ruta con {postulante} suelta).
    Route::get('postulantes/{postulante}/titulo', [PostulanteController::class, 'verTitulo'])->middleware('permiso:postulantes.index');
    Route::get('postulantes/{postulante}', [PostulanteController::class, 'show'])->middleware('permiso:postulantes.index');
    Route::post('postulantes', [PostulanteController::class, 'store'])->middleware('permiso:postulantes.store');
    Route::put('postulantes/{postulante}', [PostulanteController::class, 'update'])->middleware('permiso:postulantes.update');
    Route::patch('postulantes/{postulante}/estado', [PostulanteController::class, 'cambiarEstado'])->middleware('permiso:postulantes.update');
    Route::post('postulantes/{postulante}/activar', [PostulanteController::class, 'activarSinPago'])->middleware('permiso:postulantes.update');
    // Flujo de ingreso sin pago: activación manual del admin desde la gestión de Usuarios.
    Route::post('postulantes/{postulante}/habilitar', [PostulanteController::class, 'habilitar'])->middleware('permiso:usuarios.update');
    Route::delete('postulantes/{postulante}', [PostulanteController::class, 'destroy'])->middleware('permiso:postulantes.destroy');
});
