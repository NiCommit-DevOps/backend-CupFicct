<?php

namespace App\Modules\Academics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Http\Requests\AsignarGrupoRequest;
use App\Modules\Academics\Http\Requests\DesasignarGrupoRequest;
use App\Modules\Academics\Http\Requests\StoreGrupoRequest;
use App\Modules\Academics\Http\Requests\UpdateGrupoRequest;
use App\Modules\Academics\Http\Resources\GrupoResource;
use App\Modules\Academics\Models\Aula;
use App\Modules\Academics\Models\Grupo;
use App\Modules\Academics\Services\GrupoService;
use App\Modules\Administrative\Models\Convocatoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU09 — Gestionar Asignación de Grupos.
 */
class GrupoController extends Controller
{
    public function __construct(private readonly GrupoService $grupos)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return GrupoResource::collection($this->grupos->listar());
    }

    /** Catálogos para el formulario de grupos (aulas + turnos). */
    public function catalogos(): JsonResponse
    {
        return response()->json([
            'aulas' => Aula::query()->orderBy('nombre')->get(['id_aula', 'nombre', 'capacidad']),
            'turnos' => Grupo::TURNOS,
        ]);
    }

    /** Datos del panel de asignación: postulantes ELEGIBLE + grupos con cupo. */
    public function asignacion(): JsonResponse
    {
        $postulantes = $this->grupos->inscripcionesElegibles()->map(function ($insc) {
            return [
                'id_inscripcion' => $insc->id_inscripcion,
                'turno_preferencia' => $insc->turno_preferencia,
                'id_grupo' => $insc->id_grupo,
                'grupo' => $insc->grupo ? ['id_grupo' => $insc->grupo->id_grupo, 'sigla' => $insc->grupo->sigla] : null,
                'postulante' => $insc->postulante ? [
                    'id_postulante' => $insc->postulante->id_postulante,
                    'codigo_tramite' => $insc->postulante->codigo_tramite,
                    'nombres' => $insc->postulante->usuario?->nombres,
                    'apellidos' => $insc->postulante->usuario?->apellidos,
                    'ci' => $insc->postulante->usuario?->ci,
                ] : null,
                'carreras' => $insc->carreras->map(fn ($c) => ['id_carrera' => $c->id_carrera, 'nombre' => $c->nombre])->values(),
            ];
        });

        $activa = Convocatoria::activa();

        return response()->json([
            'convocatoria' => $activa ? ['id_convocatoria' => $activa->id_convocatoria, 'nombre' => $activa->nombre] : null,
            'grupos' => GrupoResource::collection($this->grupos->listar()),
            'postulantes' => $postulantes,
            'resumen' => $this->grupos->resumen(),
        ]);
    }

    public function show(int $grupo): GrupoResource
    {
        return new GrupoResource($this->grupos->obtener($grupo));
    }

    public function store(StoreGrupoRequest $request): JsonResponse
    {
        $grupo = $this->grupos->crear($request->validated());

        return (new GrupoResource($this->grupos->obtener($grupo->id_grupo)))->response()->setStatusCode(201);
    }

    public function update(UpdateGrupoRequest $request, int $grupo): GrupoResource
    {
        $this->grupos->actualizar($grupo, $request->validated());

        return new GrupoResource($this->grupos->obtener($grupo));
    }

    public function destroy(int $grupo): JsonResponse
    {
        $this->grupos->eliminar($grupo);

        return response()->json(['message' => 'Grupo eliminado correctamente.']);
    }

    /* ---- Asignación ---- */

    public function asignar(AsignarGrupoRequest $request): JsonResponse
    {
        $this->grupos->asignar((int) $request->validated('id_inscripcion'), (int) $request->validated('id_grupo'));

        return response()->json(['message' => 'Postulante asignado al grupo.']);
    }

    public function desasignar(DesasignarGrupoRequest $request): JsonResponse
    {
        $this->grupos->desasignar((int) $request->validated('id_inscripcion'));

        return response()->json(['message' => 'Asignación retirada.']);
    }

    /** CU09 — Creación automática de grupos (techo(total/70)) + asignación. */
    public function crearAutomatico(): JsonResponse
    {
        $r = $this->grupos->crearGruposAutomatico();

        $mensaje = "Se crearon {$r['grupos_creados']} grupo(s) para {$r['total_inscritos']} inscrito(s). "
            ."{$r['asignados']} estudiante(s) asignado(s)"
            .($r['sin_cupo'] > 0 ? ", {$r['sin_cupo']} sin cupo." : '.');

        return response()->json(['message' => $mensaje, 'resumen' => $r]);
    }

    public function asignarLote(): JsonResponse
    {
        $resumen = $this->grupos->asignarLote();

        return response()->json([
            'message' => "Asignación en lote: {$resumen['asignados']} asignados, {$resumen['sin_cupo']} sin cupo.",
            'resumen' => $resumen,
        ]);
    }

    public function rebalancear(): JsonResponse
    {
        $resumen = $this->grupos->rebalancear();

        return response()->json([
            'message' => "Rebalanceo: {$resumen['reasignados']} reasignados, {$resumen['sin_cupo']} sin cupo.",
            'resumen' => $resumen,
        ]);
    }
}
