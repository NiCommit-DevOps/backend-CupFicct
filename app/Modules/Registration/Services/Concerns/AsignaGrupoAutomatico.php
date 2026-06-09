<?php

namespace App\Modules\Registration\Services\Concerns;

use App\Modules\Academics\Models\Grupo;
use App\Modules\Registration\Models\Inscripcion;

/**
 * Asignación automática de grupo para una inscripción recién habilitada.
 *
 * Compartido por los dos flujos de habilitación del postulante: el pago en
 * pasarela (CU05, PagoService) y la activación manual del administrador
 * (CU02/CU04, PostulanteService).
 */
trait AsignaGrupoAutomatico
{
    /**
     * Reparte la inscripción en el primer grupo con cupo que respete su turno de
     * preferencia (o cualquiera si no tiene turno). Si no hay grupos o están
     * llenos, la inscripción queda sin grupo (se podrá asignar luego desde CU09).
     */
    protected function asignarGrupoAutomatico(Inscripcion $inscripcion): ?Grupo
    {
        $turno = $inscripcion->turno_preferencia;

        foreach (Grupo::orderBy('id_grupo')->get() as $grupo) {
            if ($turno !== null && Grupo::turnoNormalizado($grupo->turno) !== $turno) {
                continue;
            }

            $usados = Inscripcion::where('id_grupo', $grupo->id_grupo)->count();
            if ($usados < $grupo->capacidad_max) {
                $inscripcion->update(['id_grupo' => $grupo->id_grupo]);

                return $grupo;
            }
        }

        return null;
    }
}
