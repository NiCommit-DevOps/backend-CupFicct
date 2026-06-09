<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU06 — Intento de examen de un postulante (uno por inscripción y número).
        Schema::create('evaluacion', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->foreignId('id_inscripcion')
                ->constrained('inscripcion', 'id_inscripcion')->cascadeOnDelete();
            $table->integer('numero_examen'); // 1 | 2 | 3
            $table->string('estado', 12)->default('EN_CURSO'); // EN_CURSO | FINALIZADO
            $table->decimal('nota', 5, 2)->nullable(); // 0-100, al finalizar
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();

            $table->unique(['id_inscripcion', 'numero_examen']);
        });

        DB::statement('ALTER TABLE evaluacion ADD CONSTRAINT evaluacion_numero_check CHECK (numero_examen BETWEEN 1 AND 3)');
        DB::statement("ALTER TABLE evaluacion ADD CONSTRAINT evaluacion_estado_check CHECK (estado IN ('EN_CURSO', 'FINALIZADO'))");
        DB::statement('ALTER TABLE evaluacion ADD CONSTRAINT evaluacion_nota_check CHECK (nota IS NULL OR (nota >= 0 AND nota <= 100))');
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
