<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU06 — Las preguntas sorteadas para un intento + la opción elegida.
        Schema::create('evaluacion_pregunta', function (Blueprint $table) {
            $table->id('id_evaluacion_pregunta');
            $table->foreignId('id_evaluacion')
                ->constrained('evaluacion', 'id_evaluacion')->cascadeOnDelete();
            $table->foreignId('id_pregunta')
                ->constrained('pregunta', 'id_pregunta')->cascadeOnDelete();
            $table->char('opcion_elegida', 1)->nullable(); // A|B|C|D, null hasta responder
            $table->boolean('es_correcta')->nullable(); // se fija al corregir

            $table->unique(['id_evaluacion', 'id_pregunta']);
        });

        DB::statement("ALTER TABLE evaluacion_pregunta ADD CONSTRAINT evalpreg_opcion_check CHECK (opcion_elegida IS NULL OR opcion_elegida IN ('A', 'B', 'C', 'D'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_pregunta');
    }
};
