<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU06/CU10 — Horario de clases de una materia en un turno.
 *
 * Clave (id_materia, turno). Los días son fijos por materia; las horas cambian
 * según el turno. El aula y el turno concreto los aporta el grupo.
 */
class Horario extends Model
{
    /** Turnos con horario de clases (los grupos de turno Noche no tienen). */
    public const TURNOS = ['Mañana', 'Tarde'];

    protected $table = 'horario';

    protected $primaryKey = 'id_horario';

    public $timestamps = false;

    protected $fillable = ['id_materia', 'turno', 'dias', 'hora_inicio', 'hora_fin'];

    protected $casts = [
        'id_materia' => 'integer',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}
