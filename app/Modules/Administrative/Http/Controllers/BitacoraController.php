<?php

namespace App\Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administrative\Http\Resources\BitacoraResource;
use App\Modules\Administrative\Models\Bitacora;
use App\Modules\Administrative\Services\BitacoraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * CU11 — Visor de auditoría (solo lectura).
 *
 * Por diseño NO existen endpoints de creación/actualización/eliminación: la
 * bitácora se alimenta automáticamente vía observers y es inmutable.
 */
class BitacoraController extends Controller
{
    public function __construct(private readonly BitacoraService $bitacora)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'tabla' => ['nullable', 'string', 'max:100'],
            'operacion' => ['nullable', Rule::in(Bitacora::OPERACIONES)],
            'id_usuario' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $pagina = $this->bitacora->listar($filtros, (int) ($request->integer('per_page') ?: 15));

        return BitacoraResource::collection($pagina)
            ->additional(['tablas' => $this->bitacora->tablasDisponibles()]);
    }

    public function show(int $bitacora): BitacoraResource
    {
        return new BitacoraResource($this->bitacora->obtener($bitacora));
    }

    /** Catálogo de tablas auditadas (para el filtro del visor). */
    public function tablas(): JsonResponse
    {
        return response()->json(['tablas' => $this->bitacora->tablasDisponibles()]);
    }
}
