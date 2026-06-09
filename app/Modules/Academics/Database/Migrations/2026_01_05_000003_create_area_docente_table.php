<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU10 — Tabla intermedia: áreas de conocimiento en las que el docente está calificado.
        Schema::create('area_docente', function (Blueprint $table) {
            $table->id('id_area_docente');
            $table->foreignId('id_area')
                ->constrained('area', 'id_area')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('id_docente');
            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')
                ->cascadeOnDelete();
            $table->unique(['id_area', 'id_docente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_docente');
    }
};
