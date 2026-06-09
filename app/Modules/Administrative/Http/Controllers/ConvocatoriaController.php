<?php

namespace App\Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administrative\Http\Requests\CambiarEstadoConvocatoriaRequest;
use App\Modules\Administrative\Http\Requests\GuardarCuposRequest;
use App\Modules\Administrative\Http\Requests\StoreConvocatoriaRequest;
use App\Modules\Administrative\Http\Requests\UpdateConvocatoriaRequest;
use App\Modules\Administrative\Http\Resources\ConvocatoriaResource;
use App\Modules\Administrative\Services\ConvocatoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU19 — Gestionar Convocatoria · Aperturar Convocatoria / Control de Estados del Proceso.
 */
class ConvocatoriaController extends Controller
{
    public function __construct(private readonly ConvocatoriaService $convocatorias)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $idGestion = $request->integer('id_gestion') ?: null;

        return ConvocatoriaResource::collection($this->convocatorias->listar($idGestion));
    }

    public function show(int $convocatoria): ConvocatoriaResource
    {
        return new ConvocatoriaResource($this->convocatorias->obtener($convocatoria));
    }

    public function store(StoreConvocatoriaRequest $request): JsonResponse
    {
        $convocatoria = $this->convocatorias->crear($request->validated());

        return (new ConvocatoriaResource($convocatoria))->response()->setStatusCode(201);
    }

    public function update(UpdateConvocatoriaRequest $request, int $convocatoria): ConvocatoriaResource
    {
        return new ConvocatoriaResource($this->convocatorias->actualizar($convocatoria, $request->validated()));
    }

    /**
     * CU19 — Control de Estados del Proceso (ABIERTA / PROCESO_EVALUACION / CONCLUIDA).
     */
    public function cambiarEstado(CambiarEstadoConvocatoriaRequest $request, int $convocatoria): ConvocatoriaResource
    {
        $actualizada = $this->convocatorias->cambiarEstado($convocatoria, $request->validated()['estado']);

        return new ConvocatoriaResource($actualizada);
    }

    public function destroy(int $convocatoria): JsonResponse
    {
        $this->convocatorias->eliminar($convocatoria);

        return response()->json(['message' => 'Convocatoria eliminada correctamente.']);
    }

    /* ===================== CU08/CU19 — Cupos por convocatoria ===================== */

    /** Oferta de plazas por carrera para la convocatoria. */
    public function cupos(int $convocatoria): JsonResponse
    {
        return response()->json(['data' => $this->convocatorias->cupos($convocatoria)]);
    }

    /** Guarda los cupos por carrera de la convocatoria. */
    public function guardarCupos(GuardarCuposRequest $request, int $convocatoria): JsonResponse
    {
        $cupos = $this->convocatorias->guardarCupos($convocatoria, $request->validated()['cupos']);

        return response()->json(['data' => $cupos]);
    }
}
