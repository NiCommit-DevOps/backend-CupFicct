<?php

namespace App\Modules\Academics\Models;

use App\Modules\Access\Models\Usuario;
use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Exams\Models\Materia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Docente extends Model
{
    protected $table = 'docente';

    protected $primaryKey = 'id_docente';

    // La PK no es autoincremental: coincide con el id_usuario de la especialización 1:1.
    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'profesion',
        'carga_horaria',
        'especialidad',
        'tiene_maestria',
        'tiene_diplomado',
    ];

    protected $casts = [
        'carga_horaria' => 'integer',
        'tiene_maestria' => 'boolean',
        'tiene_diplomado' => 'boolean',
    ];

    /**
     * Usuario base de la especialización (datos personales y credenciales).
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_docente', 'id_usuario');
    }

    /**
     * Materias que el docente está calificado para dictar (de 1 a muchas).
     */
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'docente_materia', 'id_docente', 'id_materia');
    }

    /**
     * Convocatorias (procesos de admisión) en las que participa el docente.
     */
    public function convocatorias(): BelongsToMany
    {
        return $this->belongsToMany(
            Convocatoria::class,
            'docente_convocatoria',
            'id_docente',
            'id_convocatoria',
        );
    }

    /**
     * Grupos del curso preuniversitario que dicta el docente (de 1 a 4).
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'docente_grupo',
            'id_docente',
            'id_grupo',
        );
    }
}
