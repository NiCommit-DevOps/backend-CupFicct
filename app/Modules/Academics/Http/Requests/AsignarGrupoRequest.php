<?php

namespace App\Modules\Academics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_inscripcion' => ['required', 'integer', 'exists:inscripcion,id_inscripcion'],
            'id_grupo' => ['required', 'integer', 'exists:grupo,id_grupo'],
        ];
    }
}
