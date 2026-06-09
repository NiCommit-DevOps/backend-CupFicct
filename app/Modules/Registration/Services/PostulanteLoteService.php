<?php

namespace App\Modules\Registration\Services;

use App\Modules\Exams\Models\Carrera;
use App\Modules\Registration\Models\Inscripcion;
use App\Modules\Registration\Models\UnidadEducativa;
use App\Support\LectorPlanilla;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * CU14 — Carga masiva de postulantes por archivo (Excel/CSV).
 *
 * Importa un archivo lleno, registrando cada fila como un postulante (reutiliza
 * el alta estándar). Acepta CSV (con o sin BOM) y XLSX real.
 */
class PostulanteLoteService
{
    public function __construct(private readonly PostulanteService $postulantes)
    {
    }

    /* ===================== Importación ===================== */

    /**
     * Procesa el archivo: registra cada fila como postulante. No detiene el lote
     * ante un error de fila; devuelve el resumen con los errores por fila.
     *
     * @return array{total:int, creados:int, errores:array<int,array{fila:int,ci:string,motivo:string}>}
     */
    public function importar(UploadedFile $archivo): array
    {
        // Falla temprano y claro si no hay convocatoria abierta (igual que el alta).
        $this->postulantes->convocatoriaVigente()
            ?? abort(422, 'No hay una convocatoria abierta para inscripción. Aperture una convocatoria antes de cargar postulantes.');

        $filas = LectorPlanilla::leer($archivo);

        $creados = 0;
        $errores = [];

        foreach ($filas as $i => $fila) {
            $numeroFila = $i + 2; // la fila 1 es el encabezado
            $ci = trim($fila['ci'] ?? '');

            try {
                $data = $this->mapearFila($fila);

                $validador = Validator::make($data, $this->reglas(), $this->mensajes());
                if ($validador->fails()) {
                    $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => $validador->errors()->first()];

                    continue;
                }

                $this->postulantes->crear($data);
                $creados++;
            } catch (\Throwable $e) {
                $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => $e->getMessage()];
            }
        }

        return ['total' => count($filas), 'creados' => $creados, 'errores' => $errores];
    }

    /* ===================== Internos ===================== */

    /**
     * Normaliza una fecha que Excel pudo guardar como número de serie (ej. 45000)
     * a formato AAAA-MM-DD. Si ya viene como texto, la devuelve igual.
     */
    private function fechaExcel(string $valor): string
    {
        $v = trim($valor);
        if ($v === '' || ! preg_match('/^\d+(\.\d+)?$/', $v)) {
            return $v;
        }

        // Día 1 = 1900-01-01; se usa 1899-12-30 por el bug del año 1900 de Excel.
        $serial = (int) floor((float) $v);

        return (new \DateTime('1899-12-30'))->modify("+{$serial} days")->format('Y-m-d');
    }

    /**
     * Convierte una fila cruda en el arreglo que espera PostulanteService::crear,
     * resolviendo nombres a IDs (unidad educativa, carreras) y normalizando.
     *
     * @return array<string,mixed>
     */
    private function mapearFila(array $f): array
    {
        $vacio = fn ($v) => ($v === null || trim((string) $v) === '' || trim((string) $v) === '—');

        $sexo = $this->mapearSexo($f['sexo'] ?? '');
        $titulo = $this->mapearTitulo($f['titulo_de_bachiller'] ?? '');
        $idUnidad = $this->resolverUnidad($f['unidad_educativa'] ?? '');
        [$idCarrera, $idCarrera2] = $this->resolverCarreras($f['carreras'] ?? '');
        $turno = mb_strtoupper(trim($f['turno_de_preferencia'] ?? ''));

        return [
            'ci' => trim($f['ci'] ?? ''),
            'nombres' => trim($f['nombres'] ?? ''),
            'apellidos' => trim($f['apellidos'] ?? ''),
            'correo' => trim($f['correo'] ?? ''),
            'telefono1' => $vacio($f['telefono_1'] ?? '') ? null : trim($f['telefono_1']),
            'telefono2' => $vacio($f['telefono_2'] ?? '') ? null : trim($f['telefono_2']),
            'fecha_nacimiento' => $this->fechaExcel($f['fecha_de_nacimiento'] ?? ''),
            'sexo' => $sexo,
            'procedencia' => $vacio($f['procedencia'] ?? '') ? null : trim($f['procedencia']),
            'id_unidad' => $idUnidad,
            'direccion' => $vacio($f['direccion'] ?? '') ? null : trim($f['direccion']),
            'titulo_bachiller' => $titulo,
            'anio_egreso' => $vacio($f['ano_de_egreso'] ?? '') ? null : (int) $f['ano_de_egreso'],
            'otros' => $vacio($f['otros'] ?? '') ? null : trim($f['otros']),
            'id_carrera' => $idCarrera,
            'id_carrera_2' => $idCarrera2,
            'turno_preferencia' => $turno,
        ];
    }

    /** Reglas de validación por fila (espejo del alta de postulantes). */
    private function reglas(): array
    {
        $anioActual = (int) date('Y');

        return [
            'ci' => ['required', 'string', 'max:20', 'unique:usuario,ci'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100', 'unique:usuario,correo'],
            'telefono1' => ['nullable', 'string', 'max:20'],
            'telefono2' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', 'in:M,F,Otro'],
            'direccion' => ['nullable', 'string', 'max:150'],
            'id_unidad' => ['nullable', 'integer', 'exists:unidad_educativa,id_unidad'],
            'procedencia' => ['nullable', 'string', 'max:100'],
            'anio_egreso' => ['nullable', 'integer', 'min:1950', 'max:'.$anioActual],
            'otros' => ['nullable', 'string', 'max:255'],
            'id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'id_carrera_2' => ['nullable', 'integer', 'exists:carrera,id_carrera', 'different:id_carrera'],
            'turno_preferencia' => ['required', 'in:'.implode(',', Inscripcion::TURNOS)],
        ];
    }

    private function mensajes(): array
    {
        return [
            'ci.unique' => 'El CI ya está registrado.',
            'correo.unique' => 'El correo ya está registrado.',
            'correo.email' => 'El correo no es válido.',
            'id_carrera.required' => 'No se reconoció la carrera (1ª opción). Revisa que el nombre coincida con una carrera registrada.',
            'turno_preferencia.in' => 'El turno debe ser MAÑANA, TARDE o NOCHE.',
            'sexo.in' => 'El sexo debe ser Masculino, Femenino u Otro.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria (formato AAAA-MM-DD).',
        ];
    }

    private function mapearSexo(string $valor): ?string
    {
        $v = mb_strtolower(trim($valor));

        return match (true) {
            in_array($v, ['m', 'masculino'], true) => 'M',
            in_array($v, ['f', 'femenino'], true) => 'F',
            in_array($v, ['otro', 'o'], true) => 'Otro',
            default => null,
        };
    }

    private function mapearTitulo(string $valor): bool
    {
        $v = mb_strtolower(trim($valor));

        // Por defecto true (es requisito de admisión); solo "no" lo niega.
        return ! in_array($v, ['no', 'n', 'false', '0'], true);
    }

    /** Busca o crea la unidad educativa por nombre (las escuelas son texto libre). */
    private function resolverUnidad(string $nombre): ?int
    {
        $nombre = trim($nombre);
        if ($nombre === '' || $nombre === '—') {
            return null;
        }

        $unidad = UnidadEducativa::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first()
            ?? UnidadEducativa::create(['nombre' => $nombre]);

        return $unidad->id_unidad;
    }

    /**
     * Resuelve la columna "Carreras" (1ª y 2ª opción separadas por coma) a IDs.
     *
     * @return array{0:?int,1:?int}
     */
    private function resolverCarreras(string $texto): array
    {
        $nombres = array_values(array_filter(array_map('trim', explode(',', $texto)), fn ($n) => $n !== ''));

        $resolver = function (?string $nombre): ?int {
            if ($nombre === null) {
                return null;
            }

            return Carrera::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->value('id_carrera');
        };

        return [$resolver($nombres[0] ?? null), $resolver($nombres[1] ?? null)];
    }
}
