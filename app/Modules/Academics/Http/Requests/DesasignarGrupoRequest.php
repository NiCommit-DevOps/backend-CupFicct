<?php

namespace App\Modules\Academics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesasignarGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_inscripcion' => ['required', 'integer', 'exists:inscripcion,id_inscripcion'],
        ];
    }
}
