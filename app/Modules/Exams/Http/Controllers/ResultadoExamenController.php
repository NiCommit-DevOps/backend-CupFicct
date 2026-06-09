<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Http\Requests\GuardarNotasRequest;
use App\Modules\Exams\Http\Requests\ImportarNotasRequest;
use App\Modules\Exams\Services\NotaLoteService;
use App\Modules\Exams\Services\ResultadoExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU06 — Resultados de exámenes para el staff: ver y registrar/editar las notas
 * por materia (carga manual).
 */
class ResultadoExamenController extends Controller
{
    public function __construct(
        private readonly ResultadoExamenService $resultados,
        private readonly NotaLoteService $lote,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filtros = [
            'id_convocatoria' => $request->integer('id_convocatoria') ?: null,
            'id_gestion' => $request->integer('id_gestion') ?: null,
            'buscar' => $request->string('buscar')->toString() ?: null,
        ];

        return response()->json(['data' => $this->resultados->listar($filtros)]);
    }

    public function guardarNotas(GuardarNotasRequest $request, int $inscripcion): JsonResponse
    {
        $fila = $this->resultados->guardarNotas($inscripcion, $request->validated()['notas']);

        return response()->json(['data' => $fila]);
    }

    /** CU06 — Carga masiva de notas por materia desde un archivo (Excel/CSV). */
    public function importar(ImportarNotasRequest $request): JsonResponse
    {
        $resultado = $this->lote->importar(
            $request->file('archivo'),
            (int) $request->validated()['id_convocatoria'],
        );

        return response()->json([
            'message' => "Se cargaron notas de {$resultado['creados']} de {$resultado['total']} postulante(s).",
            ...$resultado,
        ]);
    }
}
