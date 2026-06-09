<?php

namespace App\Modules\Academics\Models;

use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CU09 — Grupo de examen.
 */
class Grupo extends Model
{
    /** Turnos del grupo (esquema base: capitalizado, distinto del turno de inscripción). */
    public const TURNOS = ['Mañana', 'Tarde', 'Noche'];

    /** Capacidad estándar de un grupo (= capacidad de un aula). Base del cálculo automático. */
    public const CAPACIDAD_ESTANDAR = 70;

    protected $table = 'grupo';

    protected $primaryKey = 'id_grupo';

    public $timestamps = false;

    protected $fillable = ['sigla', 'nombre', 'turno', 'capacidad_max', 'id_aula', 'id_convocatoria'];

    protected $casts = [
        'capacidad_max' => 'integer',
        'id_aula' => 'integer',
        'id_convocatoria' => 'integer',
    ];

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class, 'id_convocatoria', 'id_convocatoria');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_grupo', 'id_grupo');
    }

    /**
     * Docentes asignados a este grupo (CU10).
     */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'docente_grupo',
            'id_grupo',
            'id_docente',
        );
    }

    /**
     * Normaliza un turno de grupo ('Mañana') a la forma del turno de inscripción
     * ('MAÑANA') para poder compararlos.
     */
    public static function turnoNormalizado(?string $turno): ?string
    {
        return $turno === null ? null : mb_strtoupper($turno);
    }
}
