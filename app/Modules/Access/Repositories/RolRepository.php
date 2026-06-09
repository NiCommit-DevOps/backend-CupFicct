<?php

namespace App\Modules\Access\Repositories;

use App\Modules\Access\Models\Rol;
use Illuminate\Database\Eloquent\Collection;

class RolRepository
{
    public function all(): Collection
    {
        return Rol::query()->withCount('permisos')->orderBy('nombre')->get();
    }

    public function find(int $id): ?Rol
    {
        return Rol::with('permisos')->find($id);
    }

    public function create(array $data): Rol
    {
        return Rol::create($data);
    }

    public function update(Rol $rol, array $data): Rol
    {
        $rol->fill($data);
        $rol->save();

        return $rol;
    }

    public function delete(Rol $rol): void
    {
        // rol_permiso se elimina en cascada por la FK (ON DELETE CASCADE).
        $rol->delete();
    }

    public function sincronizarPermisos(Rol $rol, array $idsPermisos): Rol
    {
        $rol->permisos()->sync($idsPermisos);

        return $rol->load('permisos');
    }
}
