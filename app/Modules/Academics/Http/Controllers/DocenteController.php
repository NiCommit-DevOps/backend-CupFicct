<?php

namespace App\Modules\Academics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Http\Requests\StoreDocenteRequest;
use App\Modules\Academics\Http\Requests\UpdateDocenteRequest;
use App\Modules\Academics\Http\Resources\DocenteResource;
use App\Modules\Academics\Services\DocenteService;
use App\Modules\Academics\Services\GrupoService;
use App\Modules\Exams\Http\Resources\MateriaResource;
use App\Modules\Exams\Services\MateriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU10 — Gestionar Docentes y Horarios.
 */
class DocenteController extends Controller
{
    public function __construct(
        private readonly DocenteService $docentes,
        private readonly MateriaService $materias,
        private readonly GrupoService $grupos,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return DocenteResource::collection(
            $this->docentes->listar([
                'buscar' => $request->query('buscar'),
                'id_gestion' => $request->integer('id_gestion') ?: null,
                'id_convocatoria' => $request->integer('id_convocatoria') ?: null,
            ])
        );
    }

    /**
     * Catálogos (materias y grupos) para los selectores del formulario de docentes.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json([
            'materias' => MateriaResource::collection($this->materias->listar()),
            'grupos' => $this->grupos->listar()->map(fn ($g) => [
                'id_grupo' => $g->id_grupo,
                'sigla' => $g->sigla,
                'nombre' => $g->nombre,
                'turno' => $g->turno,
            ])->values(),
        ]);
    }

    public function show(int $docente): DocenteResource
    {
        return new DocenteResource($this->docentes->obtener($docente));
    }

    public function store(StoreDocenteRequest $request): JsonResponse
    {
        $docente = $this->docentes->crear($request->validated());

        return (new DocenteResource($docente))->response()->setStatusCode(201);
    }

    public function update(UpdateDocenteRequest $request, int $docente): DocenteResource
    {
        return new DocenteResource($this->docentes->actualizar($docente, $request->validated()));
    }

    public function destroy(int $docente): JsonResponse
    {
        $this->docentes->eliminar($docente);

        return response()->json(['message' => 'Docente eliminado correctamente.']);
    }
}
