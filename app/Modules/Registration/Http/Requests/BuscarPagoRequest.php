<?php

namespace App\Modules\Registration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU05 — Búsqueda de la deuda de inscripción y creación de la orden por carnet.
 */
class BuscarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'Ingresa tu número de carnet (CI).',
        ];
    }
}
