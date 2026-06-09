<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU10 — Asignación de docentes a grupos del curso preuniversitario.
        // Regla del negocio: un docente puede dictar de 1 a 4 grupos (el límite
        // superior se valida en DocenteService al sincronizar la relación).
        Schema::create('docente_grupo', function (Blueprint $table) {
            $table->id('id_docente_grupo');
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreignId('id_grupo')
                ->constrained('grupo', 'id_grupo')->cascadeOnDelete();
            $table->unique(['id_docente', 'id_grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_grupo');
    }
};
