<?php

namespace App\Modules\Academics\Services;

use App\Modules\Academics\Models\Aula;
use App\Modules\Academics\Repositories\AulaRepository;
use Illuminate\Database\Eloquent\Collection;

class AulaService
{
    public function __construct(private readonly AulaRepository $aulas)
    {
    }

    public function listar(): Collection
    {
        return $this->aulas->all();
    }

    public function obtener(int $id): Aula
    {
        return $this->aulas->find($id) ?? abort(404, 'Aula no encontrada.');
    }

    /**
     * CU17 — Registro de Ambientes. El nombre y la ubicación se componen
     * automáticamente desde piso + número de aula.
     */
    public function crear(array $data): Aula
    {
        return $this->aulas->create($this->mapear($data));
    }

    public function actualizar(int $id, array $data): Aula
    {
        return $this->aulas->update($this->obtener($id), $this->mapear($data));
    }

    public function eliminar(int $id): void
    {
        $this->aulas->delete($this->obtener($id));
    }

    /**
     * Traduce los datos del formulario (piso, numero, capacidad) a los campos
     * persistidos del esquema (nombre, ubicacion, capacidad).
     */
    private function mapear(array $data): array
    {
        $piso = (int) $data['piso'];
        $numero = (int) $data['numero'];

        return [
            'nombre' => Aula::componerNombre($piso, $numero),
            'ubicacion' => Aula::componerUbicacion($piso),
            'capacidad' => (int) $data['capacidad'],
        ];
    }
}
