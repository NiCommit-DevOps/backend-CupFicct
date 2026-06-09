<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamenAlumnoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU06 — Consulta de exámenes del postulante (solo lectura). El postulante ve
 * sus notas por materia, su promedio y su estado; no rinde el examen ni accede
 * a fechas: las notas las carga el staff manualmente.
 */
class ExamenAlumnoController extends Controller
{
    public function __construct(private readonly ExamenAlumnoService $examenes)
    {
    }

    public function misResultados(Request $request): JsonResponse
    {
        return response()->json($this->examenes->misResultados($request->user()));
    }
}
