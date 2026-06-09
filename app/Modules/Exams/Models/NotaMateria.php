<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU06 — Nota (0-100) de una materia dentro de un examen de un postulante.
 */
class NotaMateria extends Model
{
    protected $table = 'nota_materia';

    protected $primaryKey = 'id_nota_materia';

    public $timestamps = false;

    protected $fillable = [
        'id_evaluacion',
        'id_materia',
        'nota',
    ];

    protected $casts = [
        'id_evaluacion' => 'integer',
        'id_materia' => 'integer',
        'nota' => 'decimal:2',
    ];

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion', 'id_evaluacion');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}
