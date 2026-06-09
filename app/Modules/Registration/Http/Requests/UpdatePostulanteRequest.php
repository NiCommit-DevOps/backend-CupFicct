<?php

namespace App\Modules\Registration\Http\Requests;

use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU04 — Validación de la modificación de datos del postulante.
 *
 * Mismos campos que el alta; el CI y el correo se validan como únicos ignorando
 * al propio usuario. La contraseña no se modifica aquí (se gestiona en Perfil/CU18).
 */
class UpdatePostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $anioActual = (int) date('Y');
        // El parámetro de ruta {postulante} = id_usuario (especialización 1:1).
        $idUsuario = (int) $this->route('postulante');

        return [
            // Datos personales del usuario base.
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')->ignore($idUsuario, 'id_usuario')],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100', Rule::unique('usuario', 'correo')->ignore($idUsuario, 'id_usuario')],
            'telefono1' => ['required', 'string', 'max:20'],
            'telefono2' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', 'in:M,F,Otro'],
            'direccion' => ['required', 'string', 'max:150'],

            // Datos educativos (colegio = id_unidad, ciudad = procedencia).
            'id_unidad' => ['required', 'integer', 'exists:unidad_educativa,id_unidad'],
            'procedencia' => ['required', 'string', 'max:100'],
            // Documento del título de bachiller (PDF o imagen). Si no se envía un
            // archivo nuevo, se conserva el actual.
            'titulo_archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'anio_egreso' => ['nullable', 'integer', 'min:1950', 'max:'.$anioActual],
            'otros' => ['nullable', 'string', 'max:255'],

            // Solicitud de admisión (1ª opción y 2ª opción opcional para el corte).
            'id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'id_carrera_2' => ['nullable', 'integer', 'exists:carrera,id_carrera', 'different:id_carrera'],
            'turno_preferencia' => ['required', Rule::in(Inscripcion::TURNOS)],
        ];
    }
}
