<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\Permiso;
use App\Modules\Access\Models\Rol;
use App\Modules\Access\Repositories\RolRepository;
use Illuminate\Database\Eloquent\Collection;

class RolService
{
    public function __construct(private readonly RolRepository $roles)
    {
    }

    public function listar(): Collection
    {
        return $this->roles->all();
    }

    public function obtener(int $id): Rol
    {
        return $this->roles->find($id) ?? abort(404, 'Rol no encontrado.');
    }

    public function crear(array $data): Rol
    {
        return $this->roles->create($data);
    }

    public function actualizar(int $id, array $data): Rol
    {
        return $this->roles->update($this->obtener($id), $data);
    }

    public function eliminar(int $id): void
    {
        $this->roles->delete($this->obtener($id));
    }

    /**
     * CU03 — Sincronización de privilegios (reemplazo masivo de permisos del rol).
     */
    public function sincronizarPermisos(int $id, array $idsPermisos): Rol
    {
        return $this->roles->sincronizarPermisos($this->obtener($id), $idsPermisos);
    }

    /**
     * CU03 — Matriz dinámica: inventario de permisos agrupado por módulo.
     */
    public function matrizPermisos(): Collection
    {
        return Permiso::query()->orderBy('modulo')->get()->groupBy('modulo');
    }
}
