<?php

namespace App\Modules\Registration\Repositories;

use App\Modules\Registration\Models\Postulante;
use Illuminate\Database\Eloquent\Collection;

class PostulanteRepository
{
    private const RELACIONES = [
        'usuario.rol',
        'unidad',
        'inscripciones.convocatoria',
        'inscripciones.carreras',
    ];

    /**
     * @param  array{buscar?:?string,id_gestion?:?int,id_convocatoria?:?int}  $filtros
     */
    public function all(array $filtros = []): Collection
    {
        $buscar = $filtros['buscar'] ?? null;
        $idGestion = $filtros['id_gestion'] ?? null;
        $idConvocatoria = $filtros['id_convocatoria'] ?? null;

        return Postulante::query()
            ->with(self::RELACIONES)
            // Búsqueda agrupada para no romper la precedencia con los demás filtros.
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($w) use ($buscar) {
                    $w->where('codigo_tramite', 'ILIKE', "%{$buscar}%")
                        ->orWhereHas('usuario', function ($q) use ($buscar) {
                            $q->where('nombres', 'ILIKE', "%{$buscar}%")
                                ->orWhere('apellidos', 'ILIKE', "%{$buscar}%")
                                ->orWhere('ci', 'ILIKE', "%{$buscar}%")
                                ->orWhere('correo', 'ILIKE', "%{$buscar}%");
                        });
                });
            })
            // Filtra por la convocatoria de la inscripción, o por toda una gestión.
            ->when($idConvocatoria, fn ($query, $id) => $query->whereHas(
                'inscripciones',
                fn ($q) => $q->where('id_convocatoria', $id),
            ))
            ->when($idGestion, fn ($query, $id) => $query->whereHas(
                'inscripciones',
                fn ($q) => $q->whereHas('convocatoria', fn ($c) => $c->where('id_gestion', $id)),
            ))
            ->orderByDesc('codigo_tramite')
            ->get();
    }

    public function find(int $id): ?Postulante
    {
        return Postulante::with(self::RELACIONES)->find($id);
    }

    public function siguienteCodigoTramite(): int
    {
        // Código de trámite correlativo, base 100000.
        return (int) (Postulante::max('codigo_tramite') ?? 99999) + 1;
    }
}
