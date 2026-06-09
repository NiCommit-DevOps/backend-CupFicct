<?php

namespace App\Modules\Administrative\Models;

use App\Modules\Access\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * CU11 — Bitácora de auditoría.
 *
 * Tabla de SOLO inserción y lectura: cualquier intento de actualizar o eliminar
 * una entrada se bloquea a nivel de modelo para preservar la inmutabilidad del
 * registro forense.
 */
class Bitacora extends Model
{
    public const OPERACION_INSERT = 'INSERT';

    public const OPERACION_UPDATE = 'UPDATE';

    public const OPERACION_DELETE = 'DELETE';

    public const OPERACIONES = ['INSERT', 'UPDATE', 'DELETE'];

    protected $table = 'bitacora';

    protected $primaryKey = 'id_bitacora';

    public $timestamps = false;

    protected $fillable = [
        'tabla',
        'operacion',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'id_usuario',
        'ip_origen',
        'user_agent',
        'fecha',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha' => 'datetime',
    ];

    /** Inmutabilidad: la bitácora no admite UPDATE ni DELETE. */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('La bitácora es inmutable: no se permiten modificaciones.');
        });

        static::deleting(function () {
            throw new RuntimeException('La bitácora es inmutable: no se permiten eliminaciones.');
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
