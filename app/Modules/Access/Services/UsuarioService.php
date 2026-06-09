<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\Usuario;
use App\Modules\Access\Repositories\UsuarioRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    public function __construct(private readonly UsuarioRepository $usuarios)
    {
    }

    public function listar(int $perPage = 15, ?string $buscar = null): LengthAwarePaginator
    {
        return $this->usuarios->paginate($perPage, $buscar);
    }

    public function obtener(int $id): Usuario
    {
        return $this->usuarios->find($id) ?? abort(404, 'Usuario no encontrado.');
    }

    /**
     * CU02 — Alta de personal. Hashea la contraseña antes de persistir.
     */
    public function crear(array $data): Usuario
    {
        $data['contrasena'] = Hash::make($data['contrasena']);
        $data['EstaActivo'] = $data['EstaActivo'] ?? true;

        return $this->usuarios->create($data);
    }

    /**
     * CU02 — Actualización de datos / rol. Solo re-hashea si llega una contraseña nueva.
     */
    public function actualizar(int $id, array $data): Usuario
    {
        $usuario = $this->obtener($id);

        if (! empty($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        return $this->usuarios->update($usuario, $data);
    }

    /**
     * CU02 — Inhabilitación temporal (borrado lógico): alterna EstaActivo.
     */
    public function alternarEstado(int $id): Usuario
    {
        $usuario = $this->obtener($id);

        return $this->usuarios->update($usuario, [
            'EstaActivo' => ! $usuario->EstaActivo,
        ]);
    }

    /**
     * CU02 — Activa en lote a todas las cuentas inhabilitadas. Devuelve cuántas
     * se activaron.
     */
    public function activarTodos(): int
    {
        return Usuario::where('EstaActivo', false)->update(['EstaActivo' => true]);
    }
}
