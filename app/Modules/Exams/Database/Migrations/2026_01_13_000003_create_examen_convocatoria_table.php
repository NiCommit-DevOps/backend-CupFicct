<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU06 — Ventanas de los 3 exámenes (fecha/hora) de cada convocatoria.
        Schema::create('examen_convocatoria', function (Blueprint $table) {
            $table->id('id_examen_convocatoria');
            $table->foreignId('id_convocatoria')
                ->constrained('convocatoria', 'id_convocatoria')->cascadeOnDelete();
            $table->integer('numero_examen'); // 1 | 2 | 3
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin');

            $table->unique(['id_convocatoria', 'numero_examen']);
        });

        DB::statement('ALTER TABLE examen_convocatoria ADD CONSTRAINT examenconv_numero_check CHECK (numero_examen BETWEEN 1 AND 3)');
        DB::statement('ALTER TABLE examen_convocatoria ADD CONSTRAINT examenconv_fechas_check CHECK (fecha_fin > fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_convocatoria');
    }
};
