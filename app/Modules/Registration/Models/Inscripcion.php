<?php

namespace App\Modules\Registration\Models;

use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Exams\Models\Carrera;
use App\Modules\Exams\Models\Evaluacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscripcion extends Model
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const ESTADO_ELEGIBLE = 'ELEGIBLE';

    public const ESTADO_APROBADO = 'APROBADO';

    public const ESTADO_REPROBADO = 'REPROBADO';

    public const ESTADO_ADMITIDO = 'ADMITIDO';

    public const ESTADO_APROBADO_SIN_CUPO = 'APROBADO_SIN_CUPO';

    public const ESTADOS = ['PENDIENTE', 'ELEGIBLE', 'APROBADO', 'ADMITIDO', 'REPROBADO', 'APROBADO_SIN_CUPO'];

    public const TURNOS = ['MAÑANA', 'TARDE'];

    /** Nota mínima de aprobación del examen (promedio de los 3). */
    public const NOTA_APROBACION = 60;

    protected $table = 'inscripcion';

    protected $primaryKey = 'id_inscripcion';

    public $timestamps = false;

    protected $fillable = [
        'id_postulante',
        'id_grupo',
        'id_convocatoria',
        'turno_preferencia',
        'fecha_inscripcion',
        'estado_academico',
        'promedio_final',
        'id_carrera_admitida',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date:Y-m-d',
        'promedio_final' => 'decimal:2',
    ];

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_postulante', 'id_postulante');
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class, 'id_convocatoria', 'id_convocatoria');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academics\Models\Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(
            Carrera::class,
            'carrera_inscripcion',
            'id_inscripcion',
            'id_carrera',
        )->withPivot('orden')->orderByPivot('orden');
    }

    public function carreraAdmitida(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_admitida', 'id_carrera');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_inscripcion', 'id_inscripcion');
    }
}
