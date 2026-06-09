<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU08/CU19 — Oferta de plazas por carrera EN CADA convocatoria.
        // La plantilla (carrera.cupos) se copia aquí al abrir una convocatoria y
        // luego se ajusta por proceso; CU07 lee el cupo de la convocatoria activa.
        Schema::create('cupo_carrera_convocatoria', function (Blueprint $table) {
            $table->id('id_cupo');
            $table->foreignId('id_convocatoria')
                ->constrained('convocatoria', 'id_convocatoria')->cascadeOnDelete();
            $table->foreignId('id_carrera')
                ->constrained('carrera', 'id_carrera')->cascadeOnDelete();
            $table->integer('cupos')->default(0);

            // Un solo registro de cupo por (convocatoria, carrera).
            $table->unique(['id_convocatoria', 'id_carrera']);
        });

        DB::statement('ALTER TABLE cupo_carrera_convocatoria ADD CONSTRAINT ccc_cupos_check CHECK (cupos >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cupo_carrera_convocatoria');
    }
};
