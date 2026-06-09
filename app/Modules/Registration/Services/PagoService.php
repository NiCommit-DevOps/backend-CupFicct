<?php

namespace App\Modules\Registration\Services;

use App\Modules\Access\Models\Usuario;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\Pago;
use App\Modules\Registration\Models\Postulante;
use App\Modules\Registration\Services\Concerns\AsignaGrupoAutomatico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * CU05 — Procesar Pago de Inscripción.
 *
 * Orquesta el flujo completo: localizar la deuda por carnet, crear/capturar la
 * orden en PayPal y, al confirmarse el cobro, ejecutar la automatización del
 * negocio (mutar estado a ELEGIBLE, activar credenciales y asignar grupo).
 */
class PagoService
{
    use AsignaGrupoAutomatico;

    public function __construct(private readonly PayPalClient $paypal)
    {
    }

    /* ===================== Búsqueda de deuda ===================== */

    /**
     * Localiza la inscripción pendiente de pago de un postulante por su CI.
     *
     * REQUIERE que el postulante esté aprobado (estado_academico = 'ELEGIBLE')
     * por el administrador o coordinador académico. Si está en PENDIENTE, no
     * puede ver ni procesar pagos.
     *
     * @return array{usuario:Usuario,postulante:Postulante,inscripcion:Inscripcion,pagado:bool,pago:?Pago,monto:float,moneda:string}
     */
    public function buscarDeuda(string $ci): array
    {
        $usuario = Usuario::where('ci', trim($ci))->first()
            ?? abort(404, 'No se encontró ningún postulante con ese carnet.');

        $postulante = Postulante::with('usuario')->find($usuario->id_usuario)
            ?? abort(404, 'El carnet no corresponde a un postulante.');

        $inscripcion = $postulante->inscripciones()->latest('id_inscripcion')->first()
            ?? abort(404, 'El postulante no tiene una inscripción registrada.');

        // CU05 — Verificación crítica: solo postulantes ELEGIBLES pueden pagar.
        if ($inscripcion->estado_academico !== Inscripcion::ESTADO_ELEGIBLE) {
            abort(403, 'Tu inscripción no ha sido aprobada aún. Espera a que el administrador o coordinador académico te autorice para proceder con el pago.');
        }

        $pagoAprobado = Pago::where('id_inscripcion', $inscripcion->id_inscripcion)
            ->where('estado', Pago::ESTADO_APROBADO)
            ->first();

        return [
            'usuario' => $usuario,
            'postulante' => $postulante,
            'inscripcion' => $inscripcion,
            'pagado' => $pagoAprobado !== null,
            'pago' => $pagoAprobado,
            'monto' => $this->montoBob(),
            'moneda' => 'BOB',
        ];
    }

    /* ===================== Pasarela PayPal ===================== */

    /**
     * Crea la orden de PayPal por el cupo de inscripción (700 BS → divisa PayPal).
     *
     * @return array{order_id:string}
     */
    public function crearOrden(string $ci): array
    {
        $deuda = $this->buscarDeuda($ci);

        if ($deuda['pagado']) {
            abort(409, 'Esta inscripción ya fue pagada.');
        }

        $orden = $this->paypal->crearOrden($this->montoConvertido(), $this->moneda(), [
            'reference_id' => 'INSC-'.$deuda['inscripcion']->id_inscripcion,
            'description' => 'Cupo de inscripción CUP FICCT',
        ]);

        return ['order_id' => (string) ($orden['id'] ?? abort(502, 'PayPal no devolvió una orden válida.'))];
    }

    /**
     * Captura el pago en PayPal y, si queda COMPLETED, ejecuta la automatización
     * del negocio en una sola transacción.
     *
     * Si la captura NO se completa, registra el intento (RECHAZADO o PENDIENTE)
     * para la conciliación de caja (CU15) y aborta informando al comprador.
     *
     * @return array{pago:Pago,credenciales:array,grupo:?array,estado_academico:string}
     */
    public function capturar(string $ci, string $orderId): array
    {
        $captura = $this->paypal->capturarOrden($orderId);
        $estadoCaptura = $this->estadoCaptura($captura);

        if ($estadoCaptura !== 'COMPLETED') {
            $estado = $estadoCaptura === 'PENDING' ? Pago::ESTADO_PENDIENTE : Pago::ESTADO_RECHAZADO;
            $this->registrarIntentoNoAprobado($ci, $orderId, $estado);

            abort(422, $estado === Pago::ESTADO_PENDIENTE
                ? 'Tu pago quedó pendiente de confirmación en PayPal. Te avisaremos cuando se acredite.'
                : 'El pago fue rechazado por PayPal. Verifica tu método de pago e intenta nuevamente.');
        }

        $transaccionId = $this->extraerTransaccionId($captura) ?? $orderId;

        return DB::transaction(function () use ($ci, $transaccionId) {
            $deuda = $this->buscarDeuda($ci);

            // Doble verificación dentro de la transacción (idempotencia).
            if ($deuda['pagado']) {
                abort(409, 'Esta inscripción ya fue pagada.');
            }

            /** @var Inscripcion $inscripcion */
            $inscripcion = $deuda['inscripcion'];
            /** @var Usuario $usuario */
            $usuario = $deuda['usuario'];

            $monto = $this->montoBob();

            // 1. Registro de la transacción (con firma de control interno).
            $pago = Pago::create([
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'monto' => $monto,
                'moneda' => 'BOB',
                'estado' => Pago::ESTADO_APROBADO,
                'metodo' => Pago::METODO_PAYPAL,
                'transaccion_id' => $transaccionId,
                'seguridad_hash' => $this->firmar($inscripcion->id_inscripcion, $monto, $transaccionId),
                'fecha' => now(),
            ]);

            // 2. Mutación automatizada de estado: PENDIENTE → ELEGIBLE.
            $inscripcion->update(['estado_academico' => Inscripcion::ESTADO_ELEGIBLE]);

            // 3. Habilitación de la cuenta: contraseña por defecto + activación.
            $contrasena = PostulanteService::contrasenaPorDefecto($usuario->apellidos, $usuario->ci);
            $usuario->update([
                'contrasena' => Hash::make($contrasena),
                'EstaActivo' => true,
            ]);

            // 4. Asignación automática de grupo (mejor esfuerzo, respeta turno/cupo).
            $grupo = $this->asignarGrupoAutomatico($inscripcion);

            return [
                'pago' => $pago->fresh('inscripcion'),
                'credenciales' => [
                    'usuario' => $usuario->correo,
                    'contrasena' => $contrasena,
                    'esta_activo' => true,
                ],
                'grupo' => $grupo ? [
                    'id_grupo' => $grupo->id_grupo,
                    'sigla' => $grupo->sigla,
                    'nombre' => $grupo->nombre,
                    'turno' => $grupo->turno,
                ] : null,
                'estado_academico' => Inscripcion::ESTADO_ELEGIBLE,
            ];
        });
    }

    /* ===================== Historial ===================== */

    /**
     * Historial de pagos. El staff con permiso 'pagos.index' ve todas las
     * transacciones; cualquier otro usuario autenticado ve solo las suyas.
     */
    public function historial(Usuario $usuario, ?string $estado, int $perPage = 10): LengthAwarePaginator
    {
        $query = Pago::query()
            ->with('inscripcion.postulante.usuario')
            ->orderByDesc('id_pago');

        if (! $usuario->tienePermiso('pagos.index')) {
            // El id_postulante coincide con el id_usuario (especialización 1:1).
            $propias = Inscripcion::where('id_postulante', $usuario->id_usuario)->pluck('id_inscripcion');
            $query->whereIn('id_inscripcion', $propias);
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $query->paginate($perPage);
    }

    /* ===================== CU15 — Reportes de pagos ===================== */

    /**
     * CU15 — Conciliación de caja y cálculo de recaudación.
     *
     * Compila los ingresos aplicando filtros de fiscalización (rango de fechas,
     * estado, método y convocatoria) y los entrega agrupados por estado y por
     * método, junto con el resumen financiero (total recaudado, conteos y
     * promedio del ticket aprobado).
     *
     * @param  array{desde?:?string,hasta?:?string,estado?:?string,metodo?:?string,id_convocatoria?:?int}  $filtros
     */
    public function reporte(array $filtros): array
    {
        $porEstado = $this->baseReporte($filtros)
            ->selectRaw('estado, COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as monto_total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get()
            ->map(fn ($fila) => [
                'estado' => $fila->estado,
                'cantidad' => (int) $fila->cantidad,
                'monto_total' => (float) $fila->monto_total,
            ])
            ->values();

        $porMetodo = $this->baseReporte($filtros)
            ->selectRaw('metodo, COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as monto_total')
            ->groupBy('metodo')
            ->orderBy('metodo')
            ->get()
            ->map(fn ($fila) => [
                'metodo' => $fila->metodo,
                'cantidad' => (int) $fila->cantidad,
                'monto_total' => (float) $fila->monto_total,
            ])
            ->values();

        $cantidadTotal = $this->baseReporte($filtros)->count();
        $totalRecaudado = (float) $this->baseReporte($filtros)
            ->where('estado', Pago::ESTADO_APROBADO)->sum('monto');
        $cantidadAprobados = $this->baseReporte($filtros)
            ->where('estado', Pago::ESTADO_APROBADO)->count();
        $promedio = $cantidadAprobados > 0 ? round($totalRecaudado / $cantidadAprobados, 2) : 0.0;

        // Pendientes de pago: postulantes aprobados por el admin (ELEGIBLE) que
        // todavía no registran un pago aprobado (no pagaron). Es independiente de
        // si su cuenta fue activada manualmente o no.
        $pendientes = $this->pendientesDePago($filtros);
        $montoPorCobrar = round($this->montoBob() * count($pendientes), 2);

        return [
            'resumen' => [
                'total_recaudado' => $totalRecaudado,
                'moneda' => 'BOB',
                'cantidad_total' => $cantidadTotal,
                'cantidad_aprobados' => $cantidadAprobados,
                'promedio_aprobado' => $promedio,
                'cantidad_pendientes_pago' => count($pendientes),
                'monto_por_cobrar' => $montoPorCobrar,
            ],
            'por_estado' => $porEstado,
            'por_metodo' => $porMetodo,
            'pendientes_pago' => $pendientes,
            'catalogos' => [
                'estados' => Pago::ESTADOS,
                'metodos' => Pago::query()->distinct()->orderBy('metodo')->pluck('metodo')->values(),
                'convocatorias' => Convocatoria::query()
                    ->orderByDesc('id_convocatoria')
                    ->get(['id_convocatoria', 'nombre']),
            ],
        ];
    }

    /* ===================== Internos ===================== */

    /**
     * Postulantes aprobados (ELEGIBLE) que aún no pagaron (sin pago APROBADO).
     * Solo se filtra por convocatoria; el rango de fechas no aplica porque aún
     * no existe transacción.
     *
     * @param  array{id_convocatoria?:?int}  $filtros
     * @return array<int,array<string,mixed>>
     */
    private function pendientesDePago(array $filtros): array
    {
        $monto = $this->montoBob();

        return Inscripcion::query()
            ->where('estado_academico', Inscripcion::ESTADO_ELEGIBLE)
            ->whereDoesntHave('pagos', fn ($q) => $q->where('estado', Pago::ESTADO_APROBADO))
            ->when($filtros['id_convocatoria'] ?? null, fn ($q, $id) => $q->where('id_convocatoria', $id))
            ->with(['postulante.usuario', 'convocatoria'])
            ->orderBy('id_inscripcion')
            ->get()
            ->map(fn (Inscripcion $i) => [
                'id_inscripcion' => $i->id_inscripcion,
                'codigo_tramite' => $i->postulante?->codigo_tramite,
                'ci' => $i->postulante?->usuario?->ci,
                'nombres' => $i->postulante?->usuario?->nombres,
                'apellidos' => $i->postulante?->usuario?->apellidos,
                'convocatoria' => $i->convocatoria?->nombre,
                'fecha_inscripcion' => $i->fecha_inscripcion?->format('Y-m-d'),
                'monto' => $monto,
            ])
            ->values()
            ->all();
    }

    /**
     * Consulta base de pagos con los filtros de fiscalización aplicados. Devuelve
     * un Builder nuevo en cada llamada para poder derivar agregados independientes.
     */
    private function baseReporte(array $f): Builder
    {
        $query = Pago::query();

        if (! empty($f['desde'])) {
            $query->whereDate('fecha', '>=', $f['desde']);
        }
        if (! empty($f['hasta'])) {
            $query->whereDate('fecha', '<=', $f['hasta']);
        }
        if (! empty($f['estado'])) {
            $query->where('estado', $f['estado']);
        }
        if (! empty($f['metodo'])) {
            $query->where('metodo', $f['metodo']);
        }
        if (! empty($f['id_convocatoria'])) {
            $query->whereHas('inscripcion', fn ($q) => $q->where('id_convocatoria', $f['id_convocatoria']));
        }

        return $query;
    }

    /** Costo del cupo en moneda local (BOB). */
    private function montoBob(): float
    {
        return (float) config('services.paypal.inscripcion_monto_bob', 700);
    }

    /** Divisa con la que se cobra en PayPal. */
    private function moneda(): string
    {
        return (string) config('services.paypal.currency', 'USD');
    }

    /** Monto convertido a la divisa de PayPal (700 BS / tipo de cambio). */
    private function montoConvertido(): float
    {
        $rate = (float) config('services.paypal.bob_to_usd_rate', 6.96);

        return round($this->montoBob() / max($rate, 0.01), 2);
    }

    /** Firma HMAC que protege el registro frente a alteraciones de monto. */
    private function firmar(int $idInscripcion, float $monto, string $transaccionId): string
    {
        $payload = $idInscripcion.'|'.number_format($monto, 2, '.', '').'|'.$transaccionId;

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    /** Estado de la captura (nivel captura si existe; si no, el de la orden). */
    private function estadoCaptura(array $captura): ?string
    {
        return $captura['purchase_units'][0]['payments']['captures'][0]['status']
            ?? ($captura['status'] ?? null);
    }

    /**
     * CU15 — Persiste un intento de pago no aprobado (RECHAZADO/PENDIENTE) para
     * que aparezca en la conciliación de caja. Idempotente por order_id.
     */
    private function registrarIntentoNoAprobado(string $ci, string $orderId, string $estado): void
    {
        try {
            $deuda = $this->buscarDeuda($ci);
        } catch (\Throwable $e) {
            return; // Sin inscripción válida no hay nada que conciliar.
        }

        // Si ya está pagada, no se ensucia el historial con rechazos posteriores.
        if ($deuda['pagado']) {
            return;
        }

        $monto = $this->montoBob();

        // firstOrCreate por transaccion_id (= order_id): reintentos no duplican filas.
        Pago::firstOrCreate(
            ['transaccion_id' => $orderId],
            [
                'id_inscripcion' => $deuda['inscripcion']->id_inscripcion,
                'monto' => $monto,
                'moneda' => 'BOB',
                'estado' => $estado,
                'metodo' => Pago::METODO_PAYPAL,
                'seguridad_hash' => $this->firmar($deuda['inscripcion']->id_inscripcion, $monto, $orderId),
                'fecha' => now(),
            ],
        );
    }

    /** Extrae el ID de la captura (comprobante bancario) de la respuesta de PayPal. */
    private function extraerTransaccionId(array $captura): ?string
    {
        return $captura['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
    }
}
