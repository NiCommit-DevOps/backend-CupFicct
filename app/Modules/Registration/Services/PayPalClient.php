<?php

namespace App\Modules\Registration\Services;

use Illuminate\Support\Facades\Http;

/**
 * CU05 — Cliente liviano de la API REST de PayPal (Orders v2).
 *
 * Encapsula la autenticación OAuth2 (client_credentials) y las dos operaciones
 * del flujo de botón inteligente: crear orden y capturar el pago. No contiene
 * lógica de negocio; eso vive en {@see PagoService}.
 */
class PayPalClient
{
    /** Base de la API según el modo configurado (sandbox/live). */
    public function baseUrl(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /** Client ID público (lo consume el SDK del frontend para montar el botón). */
    public function clientId(): string
    {
        return (string) $this->credenciales()['client_id'];
    }

    /**
     * Crea una orden de pago con intención de captura.
     *
     * @param  array{reference_id?:string,description?:string}  $opciones
     * @return array<string,mixed>  Respuesta cruda de PayPal (incluye 'id').
     */
    public function crearOrden(float $monto, string $moneda, array $opciones = []): array
    {
        $unidad = [
            'amount' => [
                'currency_code' => $moneda,
                'value' => number_format($monto, 2, '.', ''),
            ],
        ];

        if (! empty($opciones['reference_id'])) {
            $unidad['reference_id'] = $opciones['reference_id'];
        }
        if (! empty($opciones['description'])) {
            $unidad['description'] = $opciones['description'];
        }

        $respuesta = Http::withToken($this->token())
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [$unidad],
            ]);

        if ($respuesta->failed()) {
            abort(502, 'No se pudo crear la orden de pago en PayPal.');
        }

        return $respuesta->json();
    }

    /**
     * Captura (cobra) una orden previamente aprobada por el comprador.
     *
     * Devuelve el cuerpo de PayPal tal cual, incluso ante un rechazo de negocio
     * (4xx, p. ej. INSTRUMENT_DECLINED): así {@see PagoService} puede registrar
     * el intento fallido para la conciliación. Solo aborta ante fallas técnicas
     * (5xx) o de autenticación (401/403), que no son un rechazo del comprador.
     *
     * @return array<string,mixed>  Respuesta cruda de PayPal (incluye 'status').
     */
    public function capturarOrden(string $orderId): array
    {
        $respuesta = Http::withToken($this->token())
            ->acceptJson()
            // La captura no lleva cuerpo, pero PayPal exige Content-Type JSON.
            ->withBody('', 'application/json')
            ->post($this->baseUrl()."/v2/checkout/orders/{$orderId}/capture");

        if ($respuesta->serverError() || in_array($respuesta->status(), [401, 403], true)) {
            abort(502, 'No se pudo capturar el pago en PayPal.');
        }

        return $respuesta->json() ?? [];
    }

    /* ===================== Internos ===================== */

    private function mode(): string
    {
        return (string) config('services.paypal.mode', 'sandbox');
    }

    /** @return array{client_id:?string,client_secret:?string} */
    private function credenciales(): array
    {
        return [
            'client_id' => config('services.paypal.'.$this->mode().'.client_id'),
            'client_secret' => config('services.paypal.'.$this->mode().'.client_secret'),
        ];
    }

    /** Token de acceso OAuth2 (grant_type=client_credentials). */
    private function token(): string
    {
        $cred = $this->credenciales();

        if (empty($cred['client_id']) || empty($cred['client_secret'])) {
            abort(500, 'Las credenciales de PayPal no están configuradas.');
        }

        $respuesta = Http::asForm()
            ->withBasicAuth($cred['client_id'], $cred['client_secret'])
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($respuesta->failed() || ! $respuesta->json('access_token')) {
            abort(502, 'No se pudo autenticar con PayPal.');
        }

        return (string) $respuesta->json('access_token');
    }
}
