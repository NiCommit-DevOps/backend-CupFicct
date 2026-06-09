<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\LoginRequest;
use App\Modules\Access\Http\Requests\RegisterUsuarioRequest;
use App\Modules\Access\Http\Resources\UsuarioResource;
use App\Modules\Access\Models\Rol;
use App\Modules\Access\Services\AuthService;
use App\Modules\Access\Services\UsuarioService;
use Illuminate\Http\JsonResponse;

/**
 * CU01 — Gestionar Sesión.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly UsuarioService $usuarios,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->auth->login(
            $request->string('login')->toString(),
            $request->string('password')->toString(),
            $request->string('tipo')->toString() ?: null,
        );

        return response()->json([
            'access_token' => $data['token'],
            'token_type' => 'bearer',
            'expires_in' => $this->auth->ttlSegundos(),
            'usuario' => new UsuarioResource($data['usuario']->load('rol.permisos')),
        ]);
    }

    public function me(): UsuarioResource
    {
        return new UsuarioResource($this->auth->me());
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'access_token' => $this->auth->refresh(),
            'token_type' => 'bearer',
            'expires_in' => $this->auth->ttlSegundos(),
        ]);
    }

    public function register(RegisterUsuarioRequest $request): JsonResponse
    {
        $recursosCrud = ['usuarios', 'roles'];

        $permisos = [];

        foreach ($recursosCrud as $recurso) {
            $permisos[] = ['modulo' => "$recurso.index", 'descripcion' => "Listar $recurso"];
            $permisos[] = ['modulo' => "$recurso.store", 'descripcion' => "Crear $recurso"];
            $permisos[] = ['modulo' => "$recurso.update", 'descripcion' => "Actualizar $recurso"];
            $permisos[] = ['modulo' => "$recurso.destroy", 'descripcion' => "Eliminar $recurso"];
        }

        $permisos[] = ['modulo' => 'roles.permisos', 'descripcion' => 'Sincronizar permisos de un rol'];
        $permisos[] = ['modulo' => 'permisos.index', 'descripcion' => 'Ver matriz de permisos'];

        foreach ($permisos as $permiso) {
            \App\Modules\Access\Models\Permiso::firstOrCreate(
                ['modulo' => $permiso['modulo']],
                $permiso,
            );
        }

        $roles = ['Administrador', 'Coordinador Académico', 'Docente', 'Postulante'];

        foreach ($roles as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }

        $rolAdministrador = Rol::where('nombre', 'Administrador')->first();
        $rolAdministrador->permisos()->sync(
            \App\Modules\Access\Models\Permiso::pluck('id_permiso')->all()
        );

        $usuario = $this->usuarios->crear(array_merge($request->validated(), [
            'id_rol' => $rolAdministrador->id_rol,
        ]));

        return (new UsuarioResource($usuario->load('rol')))
            ->response()
            ->setStatusCode(201);
    }
}
