<?php

namespace App\Modules\Registration\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadEducativa extends Model
{
    public const TIPOS = ['Fiscal', 'Convenio', 'Privado', 'Otro'];

    protected $table = 'unidad_educativa';

    protected $primaryKey = 'id_unidad';

    public $timestamps = false;

    protected $fillable = ['nombre', 'tipo', 'provincia'];
}
