<?php

namespace App\Modules\Academics\Services;

use App\Modules\Access\Models\Rol;
use App\Modules\Access\Models\Usuario;
use App\Modules\Academics\Models\Docente;
use App\Modules\Academics\Repositories\DocenteRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DocenteService
{
    /** Campos propios de la especialización Docente. */
    private const CAMPOS_DOCENTE = [
        'profesion', 'carga_horaria', 'especialidad', 'tiene_maestria', 'tiene_diplomado',
    ];

    public function __construct(private readonly DocenteRepository $docentes)
    {
    }

    /**
     * @param  array{buscar?:?string,id_gestion?:?int,id_convocatoria?:?int}  $filtros
     */
    public function listar(array $filtros = []): Collection
    {
        return $this->docentes->all($filtros);
    }

    public function obtener(int $id): Docente
    {
        return $this->docentes->find($id) ?? abort(404, 'Docente no encontrado.');
    }

    /**
     * CU10 — Especialización de Identidad (Registro Dual Transaccional).
     *
     * Crea en una sola transacción el Usuario base (rol 'Docente') y su perfil
     * avanzado Docente, vinculándolo además a sus áreas de conocimiento.
     */
    public function crear(array $data): Docente
    {
        return DB::transaction(function () use ($data) {
            $idRolDocente = Rol::where('nombre', 'Docente')->value('id_rol');

            $usuario = Usuario::create([
                'id_rol' => $idRolDocente,
                'ci' => $data['ci'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'correo' => $data['correo'],
                'telefono1' => $data['telefono1'] ?? null,
                'telefono2' => $data['telefono2'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo' => $data['sexo'] ?? null,
                // Contraseña por defecto generada a partir de los apellidos + CI.
                'contrasena' => Hash::make(self::contrasenaPorDefecto($data['apellidos'], $data['ci'])),
                'EstaActivo' => true,
            ]);

            // updateOrCreate: el observer de Usuario ya pudo crear la fila vacía
            // al guardarse el usuario con rol 'Docente'. Aquí se completan sus datos.
            $docente = Docente::updateOrCreate(
                ['id_docente' => $usuario->id_usuario],
                Arr::only($data, self::CAMPOS_DOCENTE),
            );

            $docente->materias()->sync($data['materias'] ?? []);
            $docente->convocatorias()->sync($data['convocatorias'] ?? []);
            $this->sincronizarGrupos($docente, $data['grupos'] ?? []);

            return $this->obtener($docente->id_docente);
        });
    }

    /**
     * Regla del negocio: un docente puede dictar de 1 a 4 grupos. El máximo lo
     * valida el FormRequest; aquí se reafirma para blindar otros orígenes.
     */
    private function sincronizarGrupos(Docente $docente, array $grupos): void
    {
        $grupos = array_values(array_unique(array_map('intval', $grupos)));

        if (count($grupos) > 4) {
            abort(422, 'Un docente puede ser asignado a un máximo de 4 grupos.');
        }

        $docente->grupos()->sync($grupos);
    }

    /**
     * CU10 — Contraseña por defecto del docente.
     *
     * Formato: inicial del 1er apellido (mayúscula) + inicial del 2º apellido
     * (minúscula) + "." + CI. Ej: "Verduguez Teran" + 16109930 → "Vt.16109930".
     * Si solo hay un apellido se usa su inicial en mayúscula.
     */
    public static function contrasenaPorDefecto(string $apellidos, string $ci): string
    {
        $partes = preg_split('/\s+/', trim($apellidos), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciales = '';
        if (isset($partes[0])) {
            $iniciales .= mb_strtoupper(mb_substr($partes[0], 0, 1));
        }
        if (isset($partes[1])) {
            $iniciales .= mb_strtolower(mb_substr($partes[1], 0, 1));
        }

        return $iniciales.'.'.trim($ci);
    }

    /**
     * CU10 — Actualiza el perfil docente y re-sincroniza sus áreas calificadas.
     * Los datos personales del usuario se gestionan desde CU02/CU18.
     */
    public function actualizar(int $id, array $data): Docente
    {
        $docente = $this->obtener($id);

        return DB::transaction(function () use ($docente, $data) {
            $docente->fill(Arr::only($data, self::CAMPOS_DOCENTE));
            $docente->save();

            if (array_key_exists('materias', $data)) {
                $docente->materias()->sync($data['materias'] ?? []);
            }

            if (array_key_exists('convocatorias', $data)) {
                $docente->convocatorias()->sync($data['convocatorias'] ?? []);
            }

            if (array_key_exists('grupos', $data)) {
                $this->sincronizarGrupos($docente, $data['grupos'] ?? []);
            }

            return $this->obtener($docente->id_docente);
        });
    }

    /**
     * CU10 — Elimina al docente. Al borrar el usuario base, la cascada física
     * (ON DELETE CASCADE) arrastra el registro de Docente y sus áreas asociadas.
     */
    public function eliminar(int $id): void
    {
        $docente = $this->obtener($id);

        if ($docente->usuario) {
            $docente->usuario->delete();

            return;
        }

        $docente->delete();
    }
}
