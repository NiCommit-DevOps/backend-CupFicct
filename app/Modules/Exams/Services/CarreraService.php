<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\Carrera;
use App\Modules\Exams\Repositories\CarreraRepository;
use Illuminate\Database\Eloquent\Collection;

class CarreraService
{
    public function __construct(private readonly CarreraRepository $carreras)
    {
    }

    public function listar(): Collection
    {
        return $this->carreras->all();
    }

    public function obtener(int $id): Carrera
    {
        return $this->carreras->find($id) ?? abort(404, 'Carrera no encontrada.');
    }

    /**
     * CU08 — Configurar Oferta Académica + Definición de Plazas Disponibles.
     */
    public function crear(array $data): Carrera
    {
        return $this->carreras->create($data);
    }

    public function actualizar(int $id, array $data): Carrera
    {
        return $this->carreras->update($this->obtener($id), $data);
    }

    public function eliminar(int $id): void
    {
        $this->carreras->delete($this->obtener($id));
    }
}
