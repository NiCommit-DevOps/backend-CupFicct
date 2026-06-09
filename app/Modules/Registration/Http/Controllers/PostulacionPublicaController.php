<?php

namespace App\Modules\Registration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Models\Carrera;
use App\Modules\Registration\Http\Requests\StorePostulacionPublicaRequest;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\UnidadEducativa;
use App\Modules\Registration\Services\PostulanteService;
use Illuminate\Http\JsonResponse;

/**
 * Auto-registro público de postulantes (landing FICCT).
 *
 * Endpoints SIN autenticación: el postulante consulta la convocatoria abierta
 * (si HOY está dentro de su ventana de inscripción) y envía su solicitud. No se
 * crea una cuenta funcional ni se procesa pago; el staff revisa luego la solicitud.
 */
class PostulacionPublicaController extends Controller
{
    public function __construct(private readonly PostulanteService $postulantes)
    {
    }

    /**
     * Convocatoria vigente para el público + catálogos del formulario.
     *
     * Si no hay convocatoria dentro de fecha, `convocatoria` es null y el front
     * oculta la sección. Los catálogos solo se devuelven cuando hay convocatoria
     * (el formulario únicamente se habilita en ese caso).
     */
    public function convocatoria(): JsonResponse
    {
        $convocatoria = $this->postulantes->convocatoriaPublica();

        return response()->json([
            'convocatoria' => $convocatoria ? [
                'id_convocatoria' => $convocatoria->id_convocatoria,
                'nombre' => $convocatoria->nombre,
                'fecha_creacion' => $convocatoria->fecha_creacion?->toDateString(),
                'fecha_limite_inscripcion' => $convocatoria->fecha_limite_inscripcion?->toDateString(),
            ] : null,
            'carreras' => $convocatoria
                ? Carrera::query()->orderBy('nombre')->get(['id_carrera', 'nombre', 'modalidad'])
                : [],
            'unidades' => $convocatoria
                ? UnidadEducativa::query()->orderBy('nombre')->get(['id_unidad', 'nombre'])
                : [],
            'turnos' => Inscripcion::TURNOS,
        ]);
    }

    /**
     * Recibe la solicitud de postulación pública y la guarda como PENDIENTE.
     */
    public function store(StorePostulacionPublicaRequest $request): JsonResponse
    {
        $postulante = $this->postulantes->registrarPublico($request->validated());

        return response()->json([
            'message' => 'Tu solicitud de postulación fue registrada correctamente. Conserva tu código de trámite.',
            'codigo_tramite' => $postulante->codigo_tramite,
        ], 201);
    }
}
