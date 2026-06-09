<?php

namespace App\Modules\Administrative\Services;

use App\Modules\Administrative\Models\Bitacora;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * CU11 — Consulta del visor de auditoría (solo lectura, con filtros forenses).
 */
class BitacoraService
{
    /**
     * Listado paginado de la bitácora con filtros por tabla, operación, operador
     * y rango de fechas, del más reciente al más antiguo.
     *
     * @param  array{tabla?:?string,operacion?:?string,id_usuario?:?int,desde?:?string,hasta?:?string}  $filtros
     */
    public function listar(array $filtros, int $perPage = 15): LengthAwarePaginator
    {
        $query = Bitacora::query()
            ->with('usuario:id_usuario,nombres,apellidos,correo')
            ->orderByDesc('id_bitacora');

        if (! empty($filtros['tabla'])) {
            $query->where('tabla', $filtros['tabla']);
        }
        if (! empty($filtros['operacion'])) {
            $query->where('operacion', $filtros['operacion']);
        }
        if (! empty($filtros['id_usuario'])) {
            $query->where('id_usuario', $filtros['id_usuario']);
        }
        if (! empty($filtros['desde'])) {
            $query->whereDate('fecha', '>=', $filtros['desde']);
        }
        if (! empty($filtros['hasta'])) {
            $query->whereDate('fecha', '<=', $filtros['hasta']);
        }

        return $query->paginate($perPage);
    }

    public function obtener(int $id): Bitacora
    {
        return Bitacora::with('usuario:id_usuario,nombres,apellidos,correo')->find($id)
            ?? abort(404, 'Entrada de bitácora no encontrada.');
    }

    /** Catálogo de tablas auditadas presentes (para el filtro del visor). */
    public function tablasDisponibles(): array
    {
        return Bitacora::query()->distinct()->orderBy('tabla')->pluck('tabla')->all();
    }
}
