<?php

namespace App\Modules\Registration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU05 — Captura del pago: requiere el carnet y el ID de orden de PayPal.
 */
class CapturarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:20'],
            'order_id' => ['required', 'string', 'max:255'],
        ];
    }
}
