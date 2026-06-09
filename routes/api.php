<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (raíz)
|--------------------------------------------------------------------------
| Las rutas de cada módulo se registran desde su propio ModuleServiceProvider
| con el prefijo "api/v1". Este archivo solo expone endpoints transversales.
*/

Route::get('/ping', fn () => response()->json([
    'app' => config('app.name'),
    'status' => 'ok',
]));
