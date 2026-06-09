<?php

namespace App\Modules\Administrative\Repositories;

use App\Modules\Administrative\Models\Convocatoria;
use Illuminate\Database\Eloquent\Collection;

class ConvocatoriaRepository
{
    public function all(?int $idGestion = null): Collection
    {
        return Convocatoria::query()
            ->with('gestion')
            ->when($idGestion, fn ($q) => $q->where('id_gestion', $idGestion))
            ->orderByDesc('id_convocatoria')
            ->get();
    }

    public function find(int $id): ?Convocatoria
    {
        return Convocatoria::with('gestion')->find($id);
    }

    public function create(array $data): Convocatoria
    {
        return Convocatoria::create($data)->load('gestion');
    }

    public function update(Convocatoria $convocatoria, array $data): Convocatoria
    {
        $convocatoria->fill($data);
        $convocatoria->save();

        return $convocatoria->load('gestion');
    }

    public function delete(Convocatoria $convocatoria): void
    {
        $convocatoria->delete();
    }
}
