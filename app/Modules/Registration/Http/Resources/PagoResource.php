<?php

namespace App\Modules\Registration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CU05 — Representación de una transacción de pago para el historial.
 */
class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pago' => $this->id_pago,
            'monto' => (float) $this->monto,
            'moneda' => $this->moneda,
            'estado' => $this->estado,
            'metodo' => $this->metodo,
            'transaccion_id' => $this->transaccion_id,
            'fecha' => $this->fecha?->toDateTimeString(),
            'inscripcion' => $this->whenLoaded('inscripcion', function () {
                $usuario = $this->inscripcion->postulante?->usuario;

                return [
                    'id_inscripcion' => $this->inscripcion->id_inscripcion,
                    'estado_academico' => $this->inscripcion->estado_academico,
                    'postulante' => $usuario ? [
                        'ci' => $usuario->ci,
                        'nombres' => $usuario->nombres,
                        'apellidos' => $usuario->apellidos,
                    ] : null,
                ];
            }),
        ];
    }
}
