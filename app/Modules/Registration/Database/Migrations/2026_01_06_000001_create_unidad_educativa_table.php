<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU04 — Catálogo de unidades educativas (colegios de procedencia).
        Schema::create('unidad_educativa', function (Blueprint $table) {
            $table->id('id_unidad');
            $table->string('nombre', 100);
            $table->string('tipo', 20)->nullable();
            $table->string('provincia', 100)->nullable();
        });

        DB::statement("ALTER TABLE unidad_educativa ADD CONSTRAINT unidad_educativa_tipo_check CHECK (tipo IS NULL OR tipo IN ('Fiscal', 'Convenio', 'Privado', 'Otro'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_educativa');
    }
};
