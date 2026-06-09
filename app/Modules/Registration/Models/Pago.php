<?php

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU05 — Pago del cupo de inscripción (derecho a examen).
 */
class Pago extends Model
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const ESTADO_APROBADO = 'APROBADO';

    public const ESTADO_RECHAZADO = 'RECHAZADO';

    public const ESTADOS = ['PENDIENTE', 'APROBADO', 'RECHAZADO'];

    public const METODO_PAYPAL = 'PAYPAL';

    protected $table = 'pago';

    protected $primaryKey = 'id_pago';

    public $timestamps = false;

    protected $fillable = [
        'id_inscripcion',
        'monto',
        'moneda',
        'estado',
        'metodo',
        'transaccion_id',
        'seguridad_hash',
        'fecha',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    protected $hidden = [
        'seguridad_hash',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }
}
