<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    /** Modalidades válidas (lista fija). */
    public const MODALIDADES = ['Presencial', 'Virtual'];

    /** Áreas válidas de la FICCT (lista fija, ampliable). */
    public const AREAS = ['Informática', 'Telecomunicaciones y Redes', 'Robótica y Automatización'];

    protected $table = 'carrera';

    protected $primaryKey = 'id_carrera';

    public $timestamps = false;

    protected $fillable = ['nombre', 'modalidad', 'codigo', 'plan', 'area', 'cupos'];

    protected $casts = [
        'cupos' => 'integer',
    ];
}
