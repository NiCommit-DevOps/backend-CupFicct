<?php

namespace App\Modules\Registration\Http\Requests;

use App\Modules\Registration\Models\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU04 — Validación del alta de postulantes por el staff.
 *
 * La contraseña no se pide: el backend la genera por defecto a partir de los
 * apellidos + CI (espejo del alta de Docentes, CU10).
 */
class StorePostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $anioActual = (int) date('Y');

        return [
            // Datos personales del usuario base (creación dual transaccional).
            // El CI NO se valida como único aquí: si ya existe, es la misma persona
            // volviendo a postular en otra convocatoria (se reutiliza su cuenta).
            // La unicidad de CI/correo frente a OTRAS personas la resuelve el servicio.
            'ci' => ['required', 'string', 'max:20'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100'],
            'telefono1' => ['required', 'string', 'max:20'],
            'telefono2' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', 'in:M,F,Otro'],
            'direccion' => ['required', 'string', 'max:150'],

            // Datos educativos (colegio = id_unidad, ciudad = procedencia).
            'id_unidad' => ['required', 'integer', 'exists:unidad_educativa,id_unidad'],
            'procedencia' => ['required', 'string', 'max:100'],
            // Título de bachiller como documento adjunto (PDF o imagen). Si se
            // sube, el postulante queda con título = Sí; si no, queda en No.
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
