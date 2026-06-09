<?php

namespace App\Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administrative\Http\Requests\CambiarEstadoGestionRequest;
use App\Modules\Administrative\Http\Requests\StoreGestionRequest;
use App\Modules\Administrative\Http\Requests\UpdateGestionRequest;
use App\Modules\Administrative\Http\Resources\GestionResource;
use App\Modules\Administrative\Services\GestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU19 — Gestionar Convocatoria · Crear Gestión / Control de Estados de la gestión.
 */
class GestionController extends Controller
{
    public function __construct(private readonly GestionService $gestiones)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return GestionResource::collection($this->gestiones->listar());
    }

    public function show(int $gestion): GestionResource
    {
        return new GestionResource($this->gestiones->obtener($gestion)->loadCount('convocatorias'));
    }

    public function store(StoreGestionRequest $request): JsonResponse
    {
        $gestion = $this->gestiones->crear($request->validated());

        return (new GestionResource($gestion))->response()->setStatusCode(201);
    }

    public function update(UpdateGestionRequest $request, int $gestion): GestionResource
    {
        return new GestionResource($this->gestiones->actualizar($gestion, $request->validated()));
    }

    /**
     * CU19 — Control de estado de la gestión (ACTIVA / CERRADA).
     */
    public function cambiarEstado(CambiarEstadoGestionRequest $request, int $gestion): GestionResource
    {
        $actualizada = $this->gestiones->cambiarEstado($gestion, $request->validated()['estado']);

        return new GestionResource($actualizada);
    }

    public function destroy(int $gestion): JsonResponse
    {
        $this->gestiones->eliminar($gestion);

        return response()->json(['message' => 'Gestión eliminada correctamente.']);
    }
}
