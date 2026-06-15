<?php

namespace App\Modules\Academics\Models;

use App\Modules\Exams\Models\Carrera;
use App\Modules\Exams\Models\Materia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CU10 — Asignación docente: una materia (área) que dicta dentro de una carrera.
 */
class DocenteCarreraMateria extends Model
{
    protected $table = 'docente_carrera_materia';

    protected $primaryKey = 'id_docente_carrera_materia';

    public $timestamps = false;

    protected $fillable = ['id_docente', 'id_carrera', 'id_materia'];

    protected $casts = [
        'id_docente' => 'integer',
        'id_carrera' => 'integer',
        'id_materia' => 'integer',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}
