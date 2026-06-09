<?php

namespace App\Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestion extends Model
{
    public const ESTADO_ACTIVA = 'ACTIVA';

    public const ESTADO_CERRADA = 'CERRADA';

    public const ESTADOS = [self::ESTADO_ACTIVA, self::ESTADO_CERRADA];

    protected $table = 'gestion';

    protected $primaryKey = 'id_gestion';

    public $timestamps = false;

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'estado'];

    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
    ];

    public function convocatorias(): HasMany
    {
        return $this->hasMany(Convocatoria::class, 'id_gestion', 'id_gestion');
    }
}
