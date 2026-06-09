<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\Materia;
use App\Modules\Registration\Models\Inscripcion;
use App\Support\LectorPlanilla;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * CU06 — Carga masiva de notas por materia desde un archivo (Excel/CSV).
 *
 * El archivo trae una fila por postulante, identificado por su CI, con una
 * columna por materia y examen: «Computación 1», «Computación 2», …, «Física 3».
 * Cada fila se cruza con la inscripción del postulante en la convocatoria
 * indicada y reutiliza la carga manual ({@see ResultadoExamenService::guardarNotas}),
 * que recalcula automáticamente el promedio y el estado (APROBADO/REPROBADO).
 *
 * No detiene el lote ante un error de fila; devuelve el resumen con los errores.
 */
class NotaLoteService
{
    private const TOTAL_EXAMENES = 3;

    public function __construct(private readonly ResultadoExamenService $resultados)
    {
    }

    /**
     * @return array{total:int, creados:int, errores:array<int,array{fila:int,ci:string,motivo:string}>}
     */
    public function importar(UploadedFile $archivo, int $idConvocatoria): array
    {
        $filas = LectorPlanilla::leer($archivo);
        $materias = Materia::orderBy('id_materia')->get();

        if ($materias->isEmpty()) {
            abort(422, 'No hay materias registradas. Registra las materias antes de cargar notas.');
        }

        $procesados = 0;
        $errores = [];

        foreach ($filas as $i => $fila) {
            $numeroFila = $i + 2; // la fila 1 es el encabezado
            $ci = trim($fila['ci'] ?? '');

            try {
                if ($ci === '') {
                    $errores[] = ['fila' => $numeroFila, 'ci' => '', 'motivo' => 'Falta el CI en la fila.'];

                    continue;
                }

                $inscripcion = $this->buscarInscripcion($ci, $idConvocatoria);
                if (! $inscripcion) {
                    $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => 'No se encontró un postulante con ese CI inscrito en la convocatoria seleccionada.'];

                    continue;
                }

                [$items, $error] = $this->mapearNotas($fila, $materias);
                if ($error !== null) {
                    $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => $error];

                    continue;
                }

                if (count($items) === 0) {
                    $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => 'La fila no tiene ninguna nota para cargar.'];

                    continue;
                }

                $this->resultados->guardarNotas($inscripcion->id_inscripcion, $items);
                $procesados++;
            } catch (\Throwable $e) {
                $errores[] = ['fila' => $numeroFila, 'ci' => $ci, 'motivo' => $e->getMessage()];
            }
        }

        return ['total' => count($filas), 'creados' => $procesados, 'errores' => $errores];
    }

    /* ===================== Internos ===================== */

    /** Ubica la inscripción del postulante (por CI) en la convocatoria indicada. */
    private function buscarInscripcion(string $ci, int $idConvocatoria): ?Inscripcion
    {
        return Inscripcion::where('id_convocatoria', $idConvocatoria)
            ->whereHas('postulante.usuario', fn ($u) => $u->where('ci', $ci))
            ->first();
    }

    /**
     * Convierte la fila en los ítems {numero_examen, id_materia, nota}, leyendo
     * la columna «{materia} {n}» de cada materia y examen. Las celdas vacías se
     * omiten (no borran notas previas).
     *
     * @param  Collection<int,Materia>  $materias
     * @return array{0:array<int,array{numero_examen:int,id_materia:int,nota:float}>, 1:?string}
     */
    private function mapearNotas(array $fila, Collection $materias): array
    {
        $items = [];

        foreach ($materias as $materia) {
            $clave = LectorPlanilla::clave($materia->nombre);

            for ($n = 1; $n <= self::TOTAL_EXAMENES; $n++) {
                $raw = trim((string) ($fila["{$clave}_{$n}"] ?? ''));
                if ($raw === '' || $raw === '—') {
                    continue;
                }

                $valor = str_replace(',', '.', $raw); // tolera coma decimal
                if (! is_numeric($valor)) {
                    return [[], "La nota de {$materia->nombre} (examen {$n}) no es un número: «{$raw}»."];
                }

                $valor = (float) $valor;
                if ($valor < 0 || $valor > 100) {
                    return [[], "La nota de {$materia->nombre} (examen {$n}) debe estar entre 0 y 100."];
                }

                $items[] = [
                    'numero_examen' => $n,
                    'id_materia' => $materia->id_materia,
                    'nota' => round($valor, 2),
                ];
            }
        }

        return [$items, null];
    }
}
