<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\StoreUsuarioRequest;
use App\Modules\Access\Http\Requests\UpdateUsuarioRequest;
use App\Modules\Access\Http\Resources\UsuarioResource;
use App\Modules\Access\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU02 — Gestionar Usuarios y Asignar Roles.
 */
class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $usuarios = $this->usuarios->listar(
            perPage: (int) $request->integer('per_page', 15),
            buscar: $request->string('buscar')->toString() ?: null,
        );

        return UsuarioResource::collection($usuarios);
    }

    public function show(int $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->obtener($usuario));
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->usuarios->crear($request->validated());

        return (new UsuarioResource($usuario->load('rol')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUsuarioRequest $request, int $usuario): UsuarioResource
    {
        $actualizado = $this->usuarios->actualizar($usuario, $request->validated());

        return new UsuarioResource($actualizado->load('rol'));
    }

    /**
     * Inhabilitación temporal (borrado lógico): alterna EstaActivo.
     */
    public function toggleEstado(int $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->alternarEstado($usuario)->load('rol'));
    }

    /** CU02 — Activa en lote a todas las cuentas inhabilitadas. */
    public function activarTodos(): JsonResponse
    {
        $activados = $this->usuarios->activarTodos();

        return response()->json([
            'message' => "Se activaron {$activados} cuenta(s).",
            'activados' => $activados,
        ]);
    }
}
