<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU04 — Especialización 1:1 de Usuario como Postulante.
        Schema::create('postulante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_postulante')->primary();
            $table->foreign('id_postulante')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();
            $table->foreignId('id_unidad')->nullable()
                ->constrained('unidad_educativa', 'id_unidad')->nullOnDelete();
            $table->integer('codigo_tramite')->unique();
            $table->string('procedencia', 100)->nullable();
            $table->boolean('titulo_bachiller')->default(true);
            $table->integer('anio_egreso')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulante');
    }
};
