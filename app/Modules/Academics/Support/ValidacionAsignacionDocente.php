<?php

namespace App\Modules\Academics\Support;

use Illuminate\Contracts\Validation\Validator;

/**
 * CU10 — Regla del negocio: las materias que el docente desea enseñar deben
 * estar dentro de la unión de áreas declaradas en sus carreras.
 */
class ValidacionAsignacionDocente
{
    /**
     * @param  array<string,mixed>  $datos
     */
    public static function materiasDentroDeAreas(Validator $validator, array $datos): void
    {
        $materias = array_map('intval', (array) ($datos['materias'] ?? []));

        if ($materias === []) {
            return;
        }

        $union = collect($datos['carreras'] ?? [])
            ->flatMap(fn ($carrera) => (array) ($carrera['areas'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        foreach ($materias as $idMateria) {
            if (! in_array($idMateria, $union, true)) {
                $validator->errors()->add(
                    'materias',
                    'Solo puedes elegir materias dentro de las áreas marcadas en las carreras.',
                );

                return;
            }
        }
    }
}
