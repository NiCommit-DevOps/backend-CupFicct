<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU04 — Carrera(s) deseada(s) por el postulante en una inscripción.
        Schema::create('carrera_inscripcion', function (Blueprint $table) {
            $table->id('id_carrera_inscripcion');
            $table->foreignId('id_carrera')
                ->constrained('carrera', 'id_carrera')->cascadeOnDelete();
            $table->foreignId('id_inscripcion')
                ->constrained('inscripcion', 'id_inscripcion')->cascadeOnDelete();
            $table->unique(['id_carrera', 'id_inscripcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera_inscripcion');
    }
};
