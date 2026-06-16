<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purga postulantes y docentes (y todo lo dependiente vía ON DELETE CASCADE:
 * inscripciones, evaluaciones, notas, pagos y pivotes de docente) borrando sus
 * usuarios. Conserva Administrador, Coordinador, catálogos y la bitácora
 * (su FK es SET NULL). Útil para dejar la base limpia antes de pruebas/entrega.
 */
class LimpiarPersonas extends Command
{
    protected $signature = 'personas:limpiar {--force : Ejecuta sin pedir confirmación (para despliegues)}';

    protected $description = 'Elimina todos los postulantes y docentes (y sus datos relacionados), dejando intactos admin, coordinador y catálogos.';

    public function handle(): int
    {
        $idsPost = DB::table('postulante')->pluck('id_postulante')->all();
        $idsDoc = DB::table('docente')->pluck('id_docente')->all();
        $ids = array_values(array_unique(array_merge($idsPost, $idsDoc)));

        if ($ids === []) {
            $this->info('No hay postulantes ni docentes que borrar. La base ya está limpia.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('Se eliminarán %d postulante(s) y %d docente(s) (%d usuario(s)) y todos sus datos relacionados.', count($idsPost), count($idsDoc), count($ids)));

        if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            DB::table('usuario')->whereIn('id_usuario', $ids)->delete();
        });

        $this->info('Listo. Postulantes y docentes eliminados.');

        foreach (['postulante', 'docente', 'inscripcion', 'evaluacion', 'nota_materia', 'pago'] as $t) {
            if (Schema::hasTable($t)) {
                $this->line(str_pad($t, 16).DB::table($t)->count());
            }
        }

        return self::SUCCESS;
    }
}
