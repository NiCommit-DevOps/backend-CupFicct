<?php

namespace App\Modules\Exams\Models;

use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CU06 — Cabecera de uno de los 3 exámenes de un postulante.
 * `nota` es el promedio de las notas de sus materias (carga manual del staff).
 */
class Evaluacion extends Model
{
    protected $table = 'evaluacion';

    protected $primaryKey = 'id_evaluacion';

    public $timestamps = false;

    protected $fillable = [
        'id_inscripcion',
        'numero_examen',
        'nota',
    ];

    protected $casts = [
        'numero_examen' => 'integer',
        'nota' => 'decimal:2',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }

    /** Notas por materia de este examen. */
    public function notasMaterias(): HasMany
    {
        return $this->hasMany(NotaMateria::class, 'id_evaluacion', 'id_evaluacion');
    }
}
