<?php

namespace App\Modules\Exams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('materia');

        return [
            'nombre' => ['sometimes', 'string', 'max:60', Rule::unique('materia', 'nombre')->ignore($id, 'id_materia')],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
