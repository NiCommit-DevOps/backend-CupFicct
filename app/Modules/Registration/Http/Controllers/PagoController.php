<?php

namespace App\Modules\Registration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Registration\Http\Requests\BuscarPagoRequest;
use App\Modules\Registration\Http\Requests\CapturarPagoRequest;
use App\Modules\Registration\Http\Resources\PagoResource;
use App\Modules\Registration\Models\Pago;
use App\Modules\Registration\Services\PagoService;
use App\Modules\Registration\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * CU05 — Procesar Pago de Inscripción.
 *
 * El flujo de cobro es público (personas externas sin cuenta funcional aún);
 * el historial requiere autenticación.
 */
class PagoController extends Controller
{
    public function __construct(
        private readonly PagoService $pagos,
        private readonly PayPalClient $paypal,
    ) {
    }

    /** Configuración pública del botón de PayPal (client_id, divisa, monto). */
    public function config(): JsonResponse
    {
        return response()->json([
            'client_id' => $this->paypal->clientId(),
            'currency' => config('services.paypal.currency', 'USD'),
            'monto' => (float) config('services.paypal.inscripcion_monto_bob', 700),
            'moneda' => 'BOB',
        ]);
    }

    /** Busca la deuda (cupo de inscripción) del postulante por su carnet. */
    public function buscar(BuscarPagoRequest $request): JsonResponse
    {
        $deuda = $this->pagos->buscarDeuda($request->validated('ci'));

        $usuario = $deuda['usuario'];
        $inscripcion = $deuda['inscripcion'];

        return response()->json([
            'pagado' => $deuda['pagado'],
            'concepto' => 'Cupo de inscripción',
            'monto' => $deuda['monto'],
            'moneda' => $deuda['moneda'],
            'postulante' => [
                'ci' => $usuario->ci,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'codigo_tramite' => $deuda['postulante']->codigo_tramite,
            ],
            'inscripcion' => [
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'estado_academico' => $inscripcion->estado_academico,
                'turno_preferencia' => $inscripcion->turno_preferencia,
            ],
        ]);
    }

    /** Crea la orden de PayPal por el cupo de inscripción. */
    public function crearOrden(BuscarPagoRequest $request): JsonResponse
    {
        return response()->json($this->pagos->crearOrden($request->validated('ci')));
    }

    /** Captura el pago aprobado y dispara la automatización del negocio. */
    public function capturar(CapturarPagoRequest $request): JsonResponse
    {
        $resultado = $this->pagos->capturar(
            $request->validated('ci'),
            $request->validated('order_id'),
        );

        return response()->json([
            'message' => 'Pago realizado con éxito. Tu inscripción quedó habilitada.',
            'pago' => new PagoResource($resultado['pago']),
            'credenciales' => $resultado['credenciales'],
            'grupo' => $resultado['grupo'],
            'estado_academico' => $resultado['estado_academico'],
        ]);
    }

    /** Historial de pagos (propio, o global si el usuario tiene permiso). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $pagos = $this->pagos->historial(
            $request->user(),
            $request->string('estado')->toString() ?: null,
            (int) ($request->integer('per_page') ?: 10),
        );

        return PagoResource::collection($pagos);
    }

    /**
     * CU15 — Reporte de conciliación de caja y recaudación total.
     *
     * Solo para staff de fiscalización (permiso 'pagos.reportes').
     */
    public function reportes(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::in(Pago::ESTADOS)],
            'metodo' => ['nullable', 'string', 'max:30'],
            'id_convocatoria' => ['nullable', 'integer', 'exists:convocatoria,id_convocatoria'],
        ]);

        return response()->json($this->pagos->reporte($filtros));
    }
}
