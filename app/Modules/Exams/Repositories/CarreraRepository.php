<?php

namespace App\Modules\Exams\Repositories;

use App\Modules\Exams\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;

class CarreraRepository
{
    public function all(): Collection
    {
        return Carrera::query()->orderBy('nombre')->get();
    }

    public function find(int $id): ?Carrera
    {
        return Carrera::find($id);
    }

    public function create(array $data): Carrera
    {
        return Carrera::create($data);
    }

    public function update(Carrera $carrera, array $data): Carrera
    {
        $carrera->fill($data);
        $carrera->save();

        return $carrera;
    }

    public function delete(Carrera $carrera): void
    {
        $carrera->delete();
    }
}
