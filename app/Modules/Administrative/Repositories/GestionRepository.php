<?php

namespace App\Modules\Administrative\Repositories;

use App\Modules\Administrative\Models\Gestion;
use Illuminate\Database\Eloquent\Collection;

class GestionRepository
{
    public function all(): Collection
    {
        return Gestion::query()
            ->withCount('convocatorias')
            ->orderByDesc('id_gestion')
            ->get();
    }

    public function find(int $id): ?Gestion
    {
        return Gestion::find($id);
    }

    public function create(array $data): Gestion
    {
        return Gestion::create($data);
    }

    public function update(Gestion $gestion, array $data): Gestion
    {
        $gestion->fill($data);
        $gestion->save();

        return $gestion;
    }

    public function delete(Gestion $gestion): void
    {
        // convocatoria se elimina en cascada por la FK (ON DELETE CASCADE).
        $gestion->delete();
    }

    /**
     * Cierra (estado CERRADA) todas las gestiones activas excepto la indicada.
     */
    public function cerrarOtrasActivas(?int $exceptoId = null): void
    {
        Gestion::query()
            ->where('estado', Gestion::ESTADO_ACTIVA)
            ->when($exceptoId, fn ($q) => $q->where('id_gestion', '!=', $exceptoId))
            ->update(['estado' => Gestion::ESTADO_CERRADA]);
    }
}
