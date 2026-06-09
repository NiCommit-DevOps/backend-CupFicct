<?php

namespace App\Modules\Academics\Repositories;

use App\Modules\Academics\Models\Docente;
use Illuminate\Database\Eloquent\Collection;

class DocenteRepository
{
    /**
     * @param  array{buscar?:?string,id_gestion?:?int,id_convocatoria?:?int}  $filtros
     */
    public function all(array $filtros = []): Collection
    {
        $buscar = $filtros['buscar'] ?? null;
        $idGestion = $filtros['id_gestion'] ?? null;
        $idConvocatoria = $filtros['id_convocatoria'] ?? null;

        return Docente::query()
            ->with(['usuario.rol', 'materias', 'convocatorias', 'grupos'])
            ->when($buscar, function ($query, $buscar) {
                $query->whereHas('usuario', function ($q) use ($buscar) {
                    $q->where('nombres', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellidos', 'ILIKE', "%{$buscar}%")
                        ->orWhere('ci', 'ILIKE', "%{$buscar}%")
                        ->orWhere('correo', 'ILIKE', "%{$buscar}%");
                });
            })
            // Filtra por convocatoria concreta o por toda una gestión.
            ->when($idConvocatoria, fn ($query, $id) => $query->whereHas(
                'convocatorias',
                fn ($q) => $q->where('convocatoria.id_convocatoria', $id),
            ))
            ->when($idGestion, fn ($query, $id) => $query->whereHas(
                'convocatorias',
                fn ($q) => $q->where('id_gestion', $id),
            ))
            ->get();
    }

    public function find(int $id): ?Docente
    {
        return Docente::with(['usuario.rol', 'materias', 'convocatorias', 'grupos'])->find($id);
    }
}
