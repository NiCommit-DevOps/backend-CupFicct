<?php

namespace App\Modules\Administrative\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50'],
            'fecha_limite_inscripcion' => ['required', 'date'],
        ];
    }
}
