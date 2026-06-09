<?php

namespace App\Modules\Administrative\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU08/CU19 — Validación del guardado de cupos por carrera en una convocatoria.
 */
class GuardarCuposRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cupos' => ['required', 'array', 'min:1'],
            'cupos.*.id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'cupos.*.cupos' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cupos.*.cupos.min' => 'Los cupos no pueden ser negativos.',
        ];
    }
}
