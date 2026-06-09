<?php

namespace App\Modules\Registration\Models;

use App\Modules\Access\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    protected $table = 'postulante';

    protected $primaryKey = 'id_postulante';

    // La PK no es autoincremental: coincide con el id_usuario (especialización 1:1).
    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_postulante',
        'id_unidad',
        'codigo_tramite',
        'procedencia',
        'direccion',
        'titulo_bachiller',
        'titulo_archivo',
        'anio_egreso',
        'otros',
    ];

    protected $casts = [
        'codigo_tramite' => 'integer',
        'titulo_bachiller' => 'boolean',
        'anio_egreso' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_postulante', 'id_usuario');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadEducativa::class, 'id_unidad', 'id_unidad');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_postulante', 'id_postulante');
    }
}
