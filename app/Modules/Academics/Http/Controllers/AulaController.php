<?php

namespace App\Modules\Academics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Http\Requests\StoreAulaRequest;
use App\Modules\Academics\Http\Requests\UpdateAulaRequest;
use App\Modules\Academics\Http\Resources\AulaResource;
use App\Modules\Academics\Services\AulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU17 — Gestionar Aulas y Recursos Físicos.
 */
class AulaController extends Controller
{
    public function __construct(private readonly AulaService $aulas)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return AulaResource::collection($this->aulas->listar());
    }

    public function show(int $aula): AulaResource
    {
        return new AulaResource($this->aulas->obtener($aula));
    }

    public function store(StoreAulaRequest $request): JsonResponse
    {
        $aula = $this->aulas->crear($request->validated());

        return (new AulaResource($aula))->response()->setStatusCode(201);
    }

    public function update(UpdateAulaRequest $request, int $aula): AulaResource
    {
        return new AulaResource($this->aulas->actualizar($aula, $request->validated()));
    }

    public function destroy(int $aula): JsonResponse
    {
        $this->aulas->eliminar($aula);

        return response()->json(['message' => 'Aula eliminada correctamente.']);
    }
}
