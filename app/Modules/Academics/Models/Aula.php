<?php

namespace App\Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    /** Módulo/edificio fijo de la FICCT usado en el nombre del aula. */
    public const MODULO = '236';

    /** Número máximo de aulas por piso (1..8). */
    public const MAX_AULA = 8;

    protected $table = 'aula';

    protected $primaryKey = 'id_aula';

    public $timestamps = false;

    protected $fillable = ['nombre', 'capacidad', 'ubicacion'];

    protected $casts = [
        'capacidad' => 'integer',
    ];

    /**
     * Nombre canónico del aula: "Aula 236-{piso}{numero}".
     * Ej: piso 1, aula 3 => "Aula 236-13".
     */
    public static function componerNombre(int $piso, int $numero): string
    {
        return sprintf('Aula %s-%d%d', self::MODULO, $piso, $numero);
    }

    /** Ubicación canónica: "Piso {piso}". */
    public static function componerUbicacion(int $piso): string
    {
        return "Piso {$piso}";
    }

    /** Piso derivado de la ubicación ("Piso 1" => 1). */
    public function getPisoAttribute(): ?int
    {
        if (! $this->ubicacion) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $this->ubicacion);

        return $digitos === '' ? null : (int) $digitos;
    }

    /** Número de aula derivado del nombre (último dígito del código). */
    public function getNumeroAttribute(): ?int
    {
        if (! $this->nombre) {
            return null;
        }

        $ultimo = substr($this->nombre, -1);

        return ctype_digit($ultimo) ? (int) $ultimo : null;
    }
}
