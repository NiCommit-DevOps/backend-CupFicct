<?php

namespace App\Modules\Access\Repositories;

use App\Modules\Access\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UsuarioRepository
{
    public function paginate(int $perPage = 15, ?string $buscar = null): LengthAwarePaginator
    {
        return Usuario::query()
            ->with('rol')
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombres', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellidos', 'ILIKE', "%{$buscar}%")
                        ->orWhere('ci', 'ILIKE', "%{$buscar}%")
                        ->orWhere('correo', 'ILIKE', "%{$buscar}%");
                });
            })
            ->orderBy('id_usuario')
            ->paginate($perPage);
    }

    public function find(int $id): ?Usuario
    {
        return Usuario::with('rol')->find($id);
    }

    public function findByLogin(string $valor): ?Usuario
    {
        return Usuario::query()
            ->with('rol.permisos')
            ->where('correo', $valor)
            ->orWhere('ci', $valor)
            ->first();
    }

    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    public function update(Usuario $usuario, array $data): Usuario
    {
        $usuario->fill($data);
        $usuario->save();

        return $usuario->refresh();
    }

    public function delete(Usuario $usuario): void
    {
        $usuario->delete();
    }
}
