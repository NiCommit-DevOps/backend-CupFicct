<?php

namespace App\Modules\Administrative\Services;

use App\Modules\Administrative\Models\Gestion;
use App\Modules\Administrative\Repositories\GestionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GestionService
{
    public function __construct(private readonly GestionRepository $gestiones)
    {
    }

    public function listar(): Collection
    {
        return $this->gestiones->all();
    }

    public function obtener(int $id): Gestion
    {
        return $this->gestiones->find($id) ?? abort(404, 'Gestión no encontrada.');
    }

    /**
     * CU19 — Crear Gestión. Si nace ACTIVA, cierra cualquier otra gestión activa
     * para garantizar la regla "solo una Gestion ACTIVA simultánea".
     */
    public function crear(array $data): Gestion
    {
        $data['estado'] ??= Gestion::ESTADO_ACTIVA;

        return DB::transaction(function () use ($data) {
            if ($data['estado'] === Gestion::ESTADO_ACTIVA) {
                $this->gestiones->cerrarOtrasActivas();
            }

            return $this->gestiones->create($data);
        });
    }

    public function actualizar(int $id, array $data): Gestion
    {
        $gestion = $this->obtener($id);

        return DB::transaction(function () use ($gestion, $data) {
            if (($data['estado'] ?? null) === Gestion::ESTADO_ACTIVA) {
                $this->gestiones->cerrarOtrasActivas($gestion->id_gestion);
            }

            return $this->gestiones->update($gestion, $data);
        });
    }

    /**
     * CU19 — Control de estado de la gestión (ACTIVA / CERRADA).
     * Al activar una gestión, las demás activas se cierran automáticamente.
     */
    public function cambiarEstado(int $id, string $estado): Gestion
    {
        $gestion = $this->obtener($id);

        return DB::transaction(function () use ($gestion, $estado) {
            if ($estado === Gestion::ESTADO_ACTIVA) {
                $this->gestiones->cerrarOtrasActivas($gestion->id_gestion);
            }

            return $this->gestiones->update($gestion, ['estado' => $estado]);
        });
    }

    public function eliminar(int $id): void
    {
        $this->gestiones->delete($this->obtener($id));
    }
}
