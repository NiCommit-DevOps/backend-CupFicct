<?php

namespace App\Modules\Academics\Repositories;

use App\Modules\Academics\Models\Grupo;
use Illuminate\Database\Eloquent\Collection;

class GrupoRepository
{
    public function all(?int $idConvocatoria = null): Collection
    {
        return Grupo::query()
            ->when($idConvocatoria, fn ($q, $id) => $q->where('id_convocatoria', $id))
            ->with('aula')
            ->withCount('inscripciones')
            ->orderBy('sigla')
            ->get();
    }

    public function find(int $id): ?Grupo
    {
        return Grupo::with('aula')->withCount('inscripciones')->find($id);
    }

    public function create(array $data): Grupo
    {
        return Grupo::create($data);
    }

    public function update(Grupo $grupo, array $data): Grupo
    {
        $grupo->fill($data);
        $grupo->save();

        return $grupo;
    }

    public function delete(Grupo $grupo): void
    {
        $grupo->delete();
    }
}
