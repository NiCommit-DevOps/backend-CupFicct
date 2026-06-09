<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\Usuario;
use App\Modules\Access\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilService
{
    public function __construct(private readonly UsuarioRepository $usuarios)
    {
    }

    /**
     * CU18 — Actualización de datos de contacto del usuario en sesión.
     */
    public function actualizarContacto(Usuario $usuario, array $data): Usuario
    {
        return $this->usuarios->update($usuario, $data);
    }

    /**
     * CU18 — Cambio seguro de contraseña: verifica la vigente antes de reemplazar.
     */
    public function cambiarPassword(Usuario $usuario, string $actual, string $nueva): Usuario
    {
        if (! Hash::check($actual, $usuario->getAuthPassword())) {
            throw ValidationException::withMessages([
                'contrasena_actual' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        return $this->usuarios->update($usuario, [
            'contrasena' => Hash::make($nueva),
        ]);
    }
}
