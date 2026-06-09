<?php

namespace App\Modules\Administrative\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CU11 — Representación de una entrada de bitácora para el visor de auditoría.
 */
class BitacoraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_bitacora' => $this->id_bitacora,
            'tabla' => $this->tabla,
            'operacion' => $this->operacion,
            'registro_id' => $this->registro_id,
            'datos_anteriores' => $this->datos_anteriores,
            'datos_nuevos' => $this->datos_nuevos,
            'ip_origen' => $this->ip_origen,
            'user_agent' => $this->user_agent,
            'fecha' => $this->fecha?->toDateTimeString(),
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id_usuario' => $this->usuario->id_usuario,
                'nombres' => $this->usuario->nombres,
                'apellidos' => $this->usuario->apellidos,
                'correo' => $this->usuario->correo,
            ] : null),
        ];
    }
}
