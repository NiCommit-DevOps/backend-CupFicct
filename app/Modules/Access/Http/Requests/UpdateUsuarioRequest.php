<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('usuario');

        return [
            'id_rol' => ['sometimes', 'nullable', 'integer', 'exists:rol,id_rol'],
            'ci' => ['sometimes', 'string', 'max:20', Rule::unique('usuario', 'ci')->ignore($id, 'id_usuario')],
            'nombres' => ['sometimes', 'string', 'max:100'],
            'apellidos' => ['sometimes', 'string', 'max:100'],
            'correo' => ['sometimes', 'email', 'max:100', Rule::unique('usuario', 'correo')->ignore($id, 'id_usuario')],
            'telefono1' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telefono2' => ['sometimes', 'nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['sometimes', 'date', 'before:today'],
            'sexo' => ['sometimes', 'nullable', 'in:M,F,Otro'],
            'contrasena' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
