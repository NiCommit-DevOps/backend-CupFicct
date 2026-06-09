<?php

namespace App\Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Convocatoria extends Model
{
    public const ESTADO_ABIERTA = 'ABIERTA';

    public const ESTADO_PROCESO_EVALUACION = 'PROCESO_EVALUACION';

    public const ESTADO_CONCLUIDA = 'CONCLUIDA';

    public const ESTADOS = [
        self::ESTADO_ABIERTA,
        self::ESTADO_PROCESO_EVALUACION,
        self::ESTADO_CONCLUIDA,
    ];

    protected $table = 'convocatoria';

    protected $primaryKey = 'id_convocatoria';

    public $timestamps = false;

    protected $fillable = [
        'id_gestion',
        'nombre',
        'fecha_creacion',
        'fecha_limite_inscripcion',
        'estado',
    ];

    protected $casts = [
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_limite_inscripcion' => 'date:Y-m-d',
    ];

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'id_gestion', 'id_gestion');
    }

    /**
     * Convocatoria "activa" del proceso: la más reciente que aún no fue
     * concluida (ABIERTA o en PROCESO_EVALUACION). Es la que usan los módulos
     * operativos (grupos, asignación) como contexto de trabajo actual.
     */
    public static function activa(): ?self
    {
        return static::where('estado', '!=', self::ESTADO_CONCLUIDA)
            ->orderByDesc('id_convocatoria')
            ->first();
    }
}
