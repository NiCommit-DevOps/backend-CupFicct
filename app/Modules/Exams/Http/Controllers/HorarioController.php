<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\HorarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU06/CU10 — Horario de clases.
 *
 * `index` expone el horario general por turno (para Materias, staff).
 * `miHorario` devuelve el horario del usuario autenticado: el docente ve sus
 * grupos (nombre, horario y aula) y el postulante su boleta de horario.
 */
class HorarioController extends Controller
{
    public function __construct(private readonly HorarioService $horarios)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->horarios->general()]);
    }

    public function miHorario(Request $request): JsonResponse
    {
        return response()->json($this->horarios->miHorario($request->user()));
    }
}
