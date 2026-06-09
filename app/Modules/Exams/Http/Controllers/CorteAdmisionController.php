<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\CorteAdmisionService;
use Illuminate\Http\JsonResponse;

/**
 * CU07 — Corte de admisión por cupos (Coordinador/Administrador).
 */
class CorteAdmisionController extends Controller
{
    public function __construct(private readonly CorteAdmisionService $corte)
    {
    }

    /** Estado actual del proceso para la convocatoria. */
    public function estado(int $convocatoria): JsonResponse
    {
        return response()->json($this->corte->estadoActual($convocatoria));
    }

    /** Ejecuta (o re-ejecuta) el corte de admisión. */
    public function ejecutar(int $convocatoria): JsonResponse
    {
        $resultado = $this->corte->ejecutar($convocatoria);

        return response()->json([
            'message' => 'Corte de admisión ejecutado correctamente.',
            ...$resultado,
        ]);
    }
}
