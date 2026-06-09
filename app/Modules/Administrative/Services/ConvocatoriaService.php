<?php

namespace App\Modules\Administrative\Services;

use App\Modules\Administrative\Models\Convocatoria;
use App\Modules\Administrative\Models\CupoCarreraConvocatoria;
use App\Modules\Administrative\Repositories\ConvocatoriaRepository;
use App\Modules\Administrative\Repositories\GestionRepository;
use App\Modules\Exams\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConvocatoriaService
{
    public function __construct(
        private readonly ConvocatoriaRepository $convocatorias,
        private readonly GestionRepository $gestiones,
    ) {
    }

    public function listar(?int $idGestion = null): Collection
    {
        return $this->convocatorias->all($idGestion);
    }

    public function obtener(int $id): Convocatoria
    {
        return $this->convocatorias->find($id) ?? abort(404, 'Convocatoria no encontrada.');
    }

    /**
     * CU19 — Aperturar Convocatoria. Queda enlazada a una gestión existente y
     * nace con estado ABIERTA por defecto.
     *
     * Al abrirse, toma una "foto" de los cupos estándar de cada carrera
     * (carrera.cupos) hacia cupo_carrera_convocatoria; esa oferta luego se ajusta
     * por proceso sin afectar a otras convocatorias.
     */
    public function crear(array $data): Convocatoria
    {
        // La gestión debe existir (404 si no).
        $this->gestiones->find($data['id_gestion']) ?? abort(422, 'La gestión indicada no existe.');

        $data['estado'] ??= Convocatoria::ESTADO_ABIERTA;
        $data['fecha_creacion'] ??= now()->toDateString();

        return DB::transaction(function () use ($data) {
            $convocatoria = $this->convocatorias->create($data);
            $this->snapshotCupos($convocatoria);

            return $convocatoria;
        });
    }

    /* ===================== CU08/CU19 — Cupos por convocatoria ===================== */

    /**
     * Oferta de plazas por carrera para una convocatoria. Si una carrera aún no
     * tiene cupo propio en la convocatoria, cae a su plantilla (carrera.cupos).
     *
     * @return array<int,array{id_carrera:int,carrera:string,codigo:string,cupos:int}>
     */
    public function cupos(int $idConvocatoria): array
    {
        $convocatoria = $this->obtener($idConvocatoria);

        $propios = CupoCarreraConvocatoria::where('id_convocatoria', $convocatoria->id_convocatoria)
            ->pluck('cupos', 'id_carrera'); // [id_carrera => cupos]

        return Carrera::query()->orderBy('nombre')->get()
            ->map(fn (Carrera $c) => [
                'id_carrera' => $c->id_carrera,
                'carrera' => $c->nombre,
                'codigo' => $c->codigo,
                'cupos' => (int) ($propios[$c->id_carrera] ?? $c->cupos),
            ])
            ->values()
            ->all();
    }

    /**
     * Guarda (upsert) los cupos por carrera de una convocatoria.
     *
     * @param  array<int,array{id_carrera:int,cupos:int}>  $items
     * @return array<int,array{id_carrera:int,carrera:string,codigo:string,cupos:int}>
     */
    public function guardarCupos(int $idConvocatoria, array $items): array
    {
        $convocatoria = $this->obtener($idConvocatoria);

        DB::transaction(function () use ($convocatoria, $items) {
            foreach ($items as $item) {
                CupoCarreraConvocatoria::updateOrCreate(
                    [
                        'id_convocatoria' => $convocatoria->id_convocatoria,
                        'id_carrera' => $item['id_carrera'],
                    ],
                    ['cupos' => $item['cupos']],
                );
            }
        });

        return $this->cupos($idConvocatoria);
    }

    /** Copia los cupos estándar de cada carrera a la convocatoria recién creada. */
    private function snapshotCupos(Convocatoria $convocatoria): void
    {
        foreach (Carrera::all() as $carrera) {
            CupoCarreraConvocatoria::firstOrCreate(
                [
                    'id_convocatoria' => $convocatoria->id_convocatoria,
                    'id_carrera' => $carrera->id_carrera,
                ],
                ['cupos' => $carrera->cupos],
            );
        }
    }

    public function actualizar(int $id, array $data): Convocatoria
    {
        return $this->convocatorias->update($this->obtener($id), $data);
    }

    /**
     * CU19 — Control de Estados del Proceso (ABIERTA / PROCESO_EVALUACION / CONCLUIDA).
     */
    public function cambiarEstado(int $id, string $estado): Convocatoria
    {
        return $this->convocatorias->update($this->obtener($id), ['estado' => $estado]);
    }

    public function eliminar(int $id): void
    {
        $this->convocatorias->delete($this->obtener($id));
    }
}
