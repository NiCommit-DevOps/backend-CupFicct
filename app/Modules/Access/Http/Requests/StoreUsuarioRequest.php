<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_rol' => ['nullable', 'integer', 'exists:rol,id_rol'],
            'ci' => ['required', 'string', 'max:20', 'unique:usuario,ci'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100', 'unique:usuario,correo'],
            'telefono1' => ['nullable', 'string', 'max:20'],
            'telefono2' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['nullable', 'in:M,F,Otro'],
            'contrasena' => ['required', 'string', 'min:8'],
            'EstaActivo' => ['sometimes', 'boolean'],
        ];
    }
}
