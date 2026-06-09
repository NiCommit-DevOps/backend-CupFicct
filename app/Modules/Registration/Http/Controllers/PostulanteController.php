<?php

namespace App\Modules\Registration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Resources\UsuarioResource;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Exams\Models\Carrera;
use App\Modules\Registration\Http\Requests\CambiarEstadoPostulanteRequest;
use App\Modules\Registration\Http\Requests\StorePostulanteRequest;
use App\Modules\Registration\Http\Requests\UpdatePostulanteRequest;
use App\Modules\Registration\Http\Resources\PostulanteResource;
use App\Modules\Registration\Http\Requests\ImportarPostulantesRequest;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\UnidadEducativa;
use App\Modules\Registration\Services\PostulanteLoteService;
use App\Modules\Registration\Services\PostulanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CU04 — Gestionar Postulantes.
 */
class PostulanteController extends Controller
{
    public function __construct(
        private readonly PostulanteService $postulantes,
        private readonly PostulanteLoteService $lote,
    ) {
    }

    /**
     * CU14 — Carga masiva de postulantes desde un archivo CSV/Excel.
     */
    public function importar(ImportarPostulantesRequest $request): JsonResponse
    {
        $resultado = $this->lote->importar($request->file('archivo'));

        return response()->json([
            'message' => "Se registraron {$resultado['creados']} de {$resultado['total']} postulante(s).",
            ...$resultado,
        ]);
    }

    /**
     * Catálogos para el formulario de alta de postulantes (staff).
     */
    public function catalogos(): JsonResponse
    {
        $convocatoria = $this->postulantes->convocatoriaVigente();

        return response()->json([
            'convocatoria' => $convocatoria ? [
                'id_convocatoria' => $convocatoria->id_convocatoria,
                'nombre' => $convocatoria->nombre,
                'fecha_limite_inscripcion' => $convocatoria->fecha_limite_inscripcion?->toDateString(),
            ] : null,
            'carreras' => Carrera::query()->orderBy('nombre')->get(['id_carrera', 'nombre', 'modalidad']),
            'unidades' => UnidadEducativa::query()->orderBy('nombre')->get(['id_unidad', 'nombre']),
            'turnos' => Inscripcion::TURNOS,
        ]);
    }

    /**
     * Alta de postulante por el staff (Registro Dual Transaccional).
     */
    public function store(StorePostulanteRequest $request): JsonResponse
    {
        $postulante = $this->postulantes->crear($request->validated());

        return (new PostulanteResource($postulante))->response()->setStatusCode(201);
    }

    /**
     * Listado para el staff (Admin gestiona, Coordinador solo lectura).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return PostulanteResource::collection(
            $this->postulantes->listar([
                'buscar' => $request->string('buscar')->toString() ?: null,
                'id_gestion' => $request->integer('id_gestion') ?: null,
                'id_convocatoria' => $request->integer('id_convocatoria') ?: null,
            ])
        );
    }

    public function show(int $postulante): PostulanteResource
    {
        return new PostulanteResource($this->postulantes->obtener($postulante));
    }

    /**
     * CU04 — Abre el documento del título de bachiller (PDF o imagen) para que el
     * staff lo revise. Se sirve en línea (inline), no como descarga forzada.
     */
    public function verTitulo(int $postulante): BinaryFileResponse
    {
        $p = $this->postulantes->obtener($postulante);

        abort_if(
            ! $p->titulo_archivo || ! Storage::disk('local')->exists($p->titulo_archivo),
            404,
            'Este postulante no tiene un título de bachiller adjunto.',
        );

        return response()->file(Storage::disk('local')->path($p->titulo_archivo));
    }

    public function update(UpdatePostulanteRequest $request, int $postulante): PostulanteResource
    {
        return new PostulanteResource($this->postulantes->actualizar($postulante, $request->validated()));
    }

    public function destroy(int $postulante): JsonResponse
    {
        $this->postulantes->eliminar($postulante);

        return response()->json(['message' => 'Postulante eliminado correctamente.']);
    }

    /** CU04 — Aprueba en lote a todos los postulantes pendientes (→ ELEGIBLE). */
    public function aprobarTodos(): JsonResponse
    {
        $aprobados = $this->postulantes->aprobarTodos();

        return response()->json([
            'message' => "Se aprobaron {$aprobados} postulante(s) pendiente(s).",
            'aprobados' => $aprobados,
        ]);
    }

    /** CU04/CU09 — Cambia el estado académico (p. ej. marcar ELEGIBLE). */
    public function cambiarEstado(CambiarEstadoPostulanteRequest $request, int $postulante): PostulanteResource
    {
        return new PostulanteResource(
            $this->postulantes->cambiarEstado($postulante, $request->validated('estado_academico'))
        );
    }

    /**
     * CU04 (Testing) — Activa un postulante sin necesidad de pago.
     *
     * Genera credenciales, activa la cuenta y cambia el estado a ELEGIBLE.
     * Útil para pruebas administrativas.
     */
    public function activarSinPago(int $postulante): JsonResponse
    {
        $resultado = $this->postulantes->activarSinPago($postulante);

        return response()->json([
            'postulante' => new PostulanteResource($resultado['usuario']->postulante),
            'credenciales' => $resultado['credenciales'],
            'grupo' => $resultado['grupo'],
            'estado_academico' => $resultado['estado_academico'],
            'mensaje' => 'Postulante activado correctamente para pruebas.',
        ], 200);
    }

    /**
     * CU02/CU04 — Habilita el acceso de un postulante desde la gestión de Usuarios.
     *
     * Flujo de ingreso sin pago: requiere que la postulación ya esté aprobada
     * (ELEGIBLE). Genera credenciales, activa la cuenta y asigna grupo. El {usuario}
     * de la ruta es el id de usuario (= id de postulante por la especialización 1:1).
     */
    public function habilitar(int $usuario): JsonResponse
    {
        $resultado = $this->postulantes->habilitarDesdeUsuario($usuario);

        return response()->json([
            'usuario' => new UsuarioResource($resultado['usuario']),
            'credenciales' => $resultado['credenciales'],
            'grupo' => $resultado['grupo'],
            'estado_academico' => $resultado['estado_academico'],
            'mensaje' => 'Postulante habilitado correctamente. Ya puede acceder al sistema.',
        ], 200);
    }
}
