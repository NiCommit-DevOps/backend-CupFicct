<?php

use App\Modules\Access\Http\Controllers\AuthController;
use App\Modules\Access\Http\Controllers\PerfilController;
use App\Modules\Access\Http\Controllers\PermisoController;
use App\Modules\Access\Http\Controllers\RolController;
use App\Modules\Access\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo Access — rutas (prefijo api/v1)
|--------------------------------------------------------------------------
*/

// CU01 — Autenticación (público)
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    // CU01 — Sesión autenticada
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    // CU18 — Perfil personal (cualquier usuario autenticado)
    Route::get('perfil', [PerfilController::class, 'show']);
    Route::put('perfil', [PerfilController::class, 'actualizarContacto']);
    Route::put('perfil/password', [PerfilController::class, 'cambiarPassword']);

    // CU02 — Gestión de usuarios
    Route::get('usuarios', [UsuarioController::class, 'index'])->middleware('permiso:usuarios.index');
    // Activación masiva (antes de la ruta con {usuario}).
    Route::post('usuarios/activar-todos', [UsuarioController::class, 'activarTodos'])->middleware('permiso:usuarios.update');
    Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->middleware('permiso:usuarios.index');
    Route::post('usuarios', [UsuarioController::class, 'store'])->middleware('permiso:usuarios.store');
    Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permiso:usuarios.update');
    Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'toggleEstado'])->middleware('permiso:usuarios.update');

    // CU03 — Matriz de permisos (catálogo, solo lectura)
    Route::get('permisos', [PermisoController::class, 'index'])->middleware('permiso:permisos.index');

    // CU03 — Gestión de roles
    Route::get('roles', [RolController::class, 'index'])->middleware('permiso:roles.index');
    Route::get('roles/{rol}', [RolController::class, 'show'])->middleware('permiso:roles.show');
    Route::post('roles', [RolController::class, 'store'])->middleware('permiso:roles.store');
    Route::put('roles/{rol}', [RolController::class, 'update'])->middleware('permiso:roles.update');
    Route::delete('roles/{rol}', [RolController::class, 'destroy'])->middleware('permiso:roles.destroy');
    Route::put('roles/{rol}/permisos', [RolController::class, 'sincronizarPermisos'])->middleware('permiso:roles.permisos');
});
