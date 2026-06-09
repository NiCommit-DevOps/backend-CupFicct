<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\CambiarPasswordRequest;
use App\Modules\Access\Http\Requests\UpdatePerfilRequest;
use App\Modules\Access\Http\Resources\UsuarioResource;
use App\Modules\Access\Services\PerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU18 — Configurar Perfil Personal (autogestión del usuario en sesión).
 */
class PerfilController extends Controller
{
    public function __construct(private readonly PerfilService $perfil)
    {
    }

    public function show(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user()->load('rol.permisos'));
    }

    public function actualizarContacto(UpdatePerfilRequest $request): UsuarioResource
    {
        $usuario = $this->perfil->actualizarContacto($request->user(), $request->validated());

        return new UsuarioResource($usuario->load('rol'));
    }

    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $this->perfil->cambiarPassword(
            $request->user(),
            $request->string('contrasena_actual')->toString(),
            $request->string('contrasena_nueva')->toString(),
        );

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
