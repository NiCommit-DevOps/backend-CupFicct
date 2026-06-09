<?php

namespace App\Modules\Academics\Observers;

use App\Modules\Access\Models\Usuario;
use App\Modules\Academics\Models\Docente;

/**
 * CU10 — Mantiene sincronizada la especialización Docente con el rol del usuario.
 *
 * Vive en el módulo Academics (que depende de Access) para no acoplar el módulo
 * de Acceso al de Grupos. Se dispara al crear/actualizar un Usuario:
 *  - Si el rol asignado es 'Docente' y aún no existe su perfil, lo crea (vacío).
 *  - Si el rol cambia a otro distinto de 'Docente', elimina la especialización
 *    (sus áreas asociadas caen por ON DELETE CASCADE).
 */
class UsuarioObserver
{
    public function saved(Usuario $usuario): void
    {
        // Solo reaccionar ante altas o cambios de rol; ignorar otras ediciones.
        if (! $usuario->wasRecentlyCreated && ! $usuario->wasChanged('id_rol')) {
            return;
        }

        $esDocente = $usuario->rol()->where('nombre', 'Docente')->exists();

        if ($esDocente) {
            Docente::firstOrCreate(['id_docente' => $usuario->id_usuario]);

            return;
        }

        // Dejó de ser docente: se retira su especialización para mantener la coherencia.
        Docente::where('id_docente', $usuario->id_usuario)->delete();
    }
}
