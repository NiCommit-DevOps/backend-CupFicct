<?php

namespace App\Modules\Administrative\Observers;

use App\Modules\Administrative\Models\Bitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * CU11 — Observer transversal de auditoría.
 *
 * Se engancha a los modelos críticos (pagos, cupos, accesos) y persiste cada
 * INSERT/UPDATE/DELETE en la bitácora, capturando el estado antes/después, el
 * operador responsable y su huella de red (IP / user agent).
 */
class BitacoraObserver
{
    /**
     * Claves sensibles que nunca deben quedar registradas en texto en la bitácora.
     */
    private const OCULTAR = ['contrasena', 'password', 'remember_token', 'seguridad_hash'];

    public function created(Model $model): void
    {
        $this->registrar($model, Bitacora::OPERACION_INSERT, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $cambios = $model->getChanges();

        if ($cambios === []) {
            return; // Nada cambió realmente: no se ensucia la bitácora.
        }

        // Estado anterior solo de las columnas que cambiaron.
        $anteriores = array_intersect_key($model->getOriginal(), $cambios);

        $this->registrar($model, Bitacora::OPERACION_UPDATE, $anteriores, $cambios);
    }

    public function deleted(Model $model): void
    {
        $this->registrar($model, Bitacora::OPERACION_DELETE, $model->getOriginal(), null);
    }

    /**
     * @param  array<string,mixed>|null  $antes
     * @param  array<string,mixed>|null  $despues
     */
    private function registrar(Model $model, string $operacion, ?array $antes, ?array $despues): void
    {
        Bitacora::create([
            'tabla' => $model->getTable(),
            'operacion' => $operacion,
            'registro_id' => $model->getKey() !== null ? (string) $model->getKey() : null,
            'datos_anteriores' => $antes !== null ? $this->depurar($antes) : null,
            'datos_nuevos' => $despues !== null ? $this->depurar($despues) : null,
            'id_usuario' => $this->operador(),
            'ip_origen' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'fecha' => now(),
        ]);
    }

    /** Reemplaza el valor de las claves sensibles por un marcador. */
    private function depurar(array $datos): array
    {
        foreach (self::OCULTAR as $clave) {
            if (array_key_exists($clave, $datos)) {
                $datos[$clave] = '***';
            }
        }

        return $datos;
    }

    /** ID del operador autenticado (api), o null en acciones públicas/de sistema. */
    private function operador(): ?int
    {
        try {
            $id = Auth::guard('api')->id();

            return $id !== null ? (int) $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function ip(): ?string
    {
        try {
            return request()->ip();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function userAgent(): ?string
    {
        try {
            return request()->userAgent();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
