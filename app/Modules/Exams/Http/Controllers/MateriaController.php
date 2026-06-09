<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Http\Requests\StoreMateriaRequest;
use App\Modules\Exams\Http\Requests\UpdateMateriaRequest;
use App\Modules\Exams\Http\Resources\MateriaResource;
use App\Modules\Exams\Services\MateriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU06/CU10 — Gestión del catálogo de materias.
 */
class MateriaController extends Controller
{
    public function __construct(private readonly MateriaService $materias)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return MateriaResource::collection(
            $this->materias->listar($request->string('buscar')->toString() ?: null)
        );
    }

    public function store(StoreMateriaRequest $request): JsonResponse
    {
        $materia = $this->materias->crear($request->validated());

        return (new MateriaResource($materia))->response()->setStatusCode(201);
    }

    public function update(UpdateMateriaRequest $request, int $materia): MateriaResource
    {
        return new MateriaResource($this->materias->actualizar($materia, $request->validated()));
    }

    public function destroy(int $materia): JsonResponse
    {
        $this->materias->eliminar($materia);

        return response()->json(['message' => 'Materia eliminada correctamente.']);
    }
}
