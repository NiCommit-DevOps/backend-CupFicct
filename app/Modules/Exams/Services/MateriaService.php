<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\Materia;
use Illuminate\Database\Eloquent\Collection;

/**
 * CU06/CU10 — Gestión del catálogo de materias evaluadas.
 */
class MateriaService
{
    public function listar(?string $buscar = null): Collection
    {
        return Materia::query()
            ->withCount('docentes')
            ->when($buscar, fn ($q, $b) => $q->where('nombre', 'ILIKE', "%{$b}%"))
            ->orderBy('nombre')
            ->get();
    }

    public function obtener(int $id): Materia
    {
        return Materia::withCount('docentes')->find($id) ?? abort(404, 'Materia no encontrada.');
    }

    public function crear(array $data): Materia
    {
        return Materia::create($data);
    }

    public function actualizar(int $id, array $data): Materia
    {
        $materia = $this->obtener($id);
        $materia->update($data);

        return $materia;
    }

    public function eliminar(int $id): void
    {
        $this->obtener($id)->delete();
    }
}
