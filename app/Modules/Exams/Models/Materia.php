<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\Docente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * CU06/CU10 — Materia evaluada (Computación, Matemáticas, Inglés, Física…).
 * El staff registra la nota de cada materia en cada examen y los docentes
 * dictan una o varias materias.
 */
class Materia extends Model
{
    protected $table = 'materia';

    protected $primaryKey = 'id_materia';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    /** Docentes que dictan esta materia. */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_materia', 'id_materia', 'id_docente');
    }
}
