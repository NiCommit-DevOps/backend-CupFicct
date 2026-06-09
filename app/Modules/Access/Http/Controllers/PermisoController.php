<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Services\RolService;
use Illuminate\Http\JsonResponse;

/**
 * CU03 — Matriz dinámica de permisos (catálogo cerrado, solo lectura).
 */
class PermisoController extends Controller
{
    public function __construct(private readonly RolService $roles)
    {
    }

    /**
     * Inventario de permisos agrupado por componente lógico (modulo).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->roles->matrizPermisos(),
        ]);
    }
}
