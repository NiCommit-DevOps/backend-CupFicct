<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU06 — Banco global de preguntas de opción múltiple (4 opciones).
        Schema::create('pregunta', function (Blueprint $table) {
            $table->id('id_pregunta');
            $table->foreignId('id_materia')
                ->constrained('materia', 'id_materia')->cascadeOnDelete();
            $table->text('enunciado');
            $table->string('opcion_a', 255);
            $table->string('opcion_b', 255);
            $table->string('opcion_c', 255);
            $table->string('opcion_d', 255);
            $table->char('correcta', 1); // A | B | C | D
            $table->boolean('activa')->default(true);
        });

        DB::statement("ALTER TABLE pregunta ADD CONSTRAINT pregunta_correcta_check CHECK (correcta IN ('A', 'B', 'C', 'D'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pregunta');
    }
};
