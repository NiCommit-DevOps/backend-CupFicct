<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU10/CU19 — Participación de un docente en convocatorias (N:M).
        // Un docente puede integrar el plantel de varios procesos de admisión.
        Schema::create('docente_convocatoria', function (Blueprint $table) {
            $table->id('id_docente_convocatoria');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreignId('id_convocatoria')
                ->constrained('convocatoria', 'id_convocatoria')->cascadeOnDelete();
            $table->unique(['id_docente', 'id_convocatoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_convocatoria');
    }
};
