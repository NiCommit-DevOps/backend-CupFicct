<?php

namespace App\Modules\Access\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida que el usuario autenticado posea uno de los permisos requeridos.
 * Uso en rutas: ->middleware('permiso:usuarios.store')
 * Acepta varios códigos: ->middleware('permiso:roles.update,roles.store')
 */
class CheckPermiso
{
    public function handle(Request $request, Closure $next, string ...$codigos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        foreach ($codigos as $codigo) {
            if ($usuario->tienePermiso($codigo)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'No tiene permiso para realizar esta acción.',
        ], 403);
    }
}
