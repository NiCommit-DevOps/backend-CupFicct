<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\StoreRolRequest;
use App\Modules\Access\Http\Requests\SyncPermisosRequest;
use App\Modules\Access\Http\Requests\UpdateRolRequest;
use App\Modules\Access\Http\Resources\RolResource;
use App\Modules\Access\Services\RolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU03 — Gestionar Roles y Asignar Permisos.
 */
class RolController extends Controller
{
    public function __construct(private readonly RolService $roles)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return RolResource::collection($this->roles->listar());
    }

    public function show(int $rol): RolResource
    {
        return new RolResource($this->roles->obtener($rol)->load('permisos'));
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $rol = $this->roles->crear($request->validated());

        return (new RolResource($rol))->response()->setStatusCode(201);
    }

    public function update(UpdateRolRequest $request, int $rol): RolResource
    {
        return new RolResource($this->roles->actualizar($rol, $request->validated()));
    }

    public function destroy(int $rol): JsonResponse
    {
        $this->roles->eliminar($rol);

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }

    /**
     * Sincronización de privilegios (reemplazo masivo de permisos del rol).
     */
    public function sincronizarPermisos(SyncPermisosRequest $request, int $rol): RolResource
    {
        $actualizado = $this->roles->sincronizarPermisos($rol, $request->validated()['permisos']);

        return new RolResource($actualizado);
    }
}
