<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\HistorialAcademicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU16 — Historial Académico (solo lectura) de gestiones concluidas.
 */
class HistorialAcademicoController extends Controller
{
    public function __construct(private readonly HistorialAcademicoService $historial)
    {
    }

    public function buscar(Request $request): JsonResponse
    {
        $termino = $request->string('buscar')->toString();

        return response()->json(['data' => $this->historial->buscar($termino)]);
    }
}
