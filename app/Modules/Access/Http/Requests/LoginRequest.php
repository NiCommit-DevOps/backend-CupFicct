<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],     // correo o ci
            'password' => ['required', 'string'],
            'tipo' => ['nullable', 'in:estudiante,docente,administrativo'], // pestaña del portal
        ];
    }
}
