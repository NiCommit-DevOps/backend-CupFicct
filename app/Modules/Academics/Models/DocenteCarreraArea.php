<?php

namespace App\Modules\Academics\Models;

use App\Modules\Exams\Models\Materia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU10 — Área (materia) en la que el docente es profesional dentro de una
 * carrera. La carrera es texto libre (no es la carrera de inscripción).
 */
class DocenteCarreraArea extends Model
{
    protected $table = 'docente_carrera_area';

    protected $primaryKey = 'id_docente_carrera_area';

    public $timestamps = false;

    protected $fillable = ['id_docente', 'carrera', 'id_materia'];

    protected $casts = [
        'id_docente' => 'integer',
        'id_materia' => 'integer',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}
