<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->user()->getKey();

        return [
            // Datos personales editables (CU18: no se permite cambiar CI ni rol).
            'nombres' => ['sometimes', 'string', 'max:100'],
            'apellidos' => ['sometimes', 'string', 'max:100'],
            'fecha_nacimiento' => ['sometimes', 'nullable', 'date', 'before:today'],
            'sexo' => ['sometimes', 'nullable', 'in:M,F,Otro'],
            'correo' => ['sometimes', 'email', 'max:100', Rule::unique('usuario', 'correo')->ignore($id, 'id_usuario')],
            'telefono1' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telefono2' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
