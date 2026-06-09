<?php

namespace App\Modules\Academics\Repositories;

use App\Modules\Academics\Models\Aula;
use Illuminate\Database\Eloquent\Collection;

class AulaRepository
{
    public function all(): Collection
    {
        return Aula::query()->orderBy('nombre')->get();
    }

    public function find(int $id): ?Aula
    {
        return Aula::find($id);
    }

    public function create(array $data): Aula
    {
        return Aula::create($data);
    }

    public function update(Aula $aula, array $data): Aula
    {
        $aula->fill($data);
        $aula->save();

        return $aula;
    }

    public function delete(Aula $aula): void
    {
        $aula->delete();
    }
}
