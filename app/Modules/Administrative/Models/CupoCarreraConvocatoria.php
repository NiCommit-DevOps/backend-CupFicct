<?php

namespace App\Modules\Administrative\Models;

use App\Modules\Exams\Models\Carrera;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU08/CU19 — Cupo (plazas) de una carrera dentro de una convocatoria concreta.
 */
class CupoCarreraConvocatoria extends Model
{
    protected $table = 'cupo_carrera_convocatoria';

    protected $primaryKey = 'id_cupo';

    public $timestamps = false;

    protected $fillable = [
        'id_convocatoria',
        'id_carrera',
        'cupos',
    ];

    protected $casts = [
        'cupos' => 'integer',
    ];

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class, 'id_convocatoria', 'id_convocatoria');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}
