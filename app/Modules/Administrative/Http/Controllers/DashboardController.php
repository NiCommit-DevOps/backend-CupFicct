<?php

namespace App\Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administrative\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU13 — Dashboard administrativo (KPIs y gráficas).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $idConvocatoria = $request->integer('id_convocatoria') ?: null;

        return response()->json($this->dashboard->metricas($idConvocatoria));
    }
}
