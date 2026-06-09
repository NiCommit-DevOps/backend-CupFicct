<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrera', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->string('nombre', 100);
            $table->string('modalidad', 50)->nullable();
            $table->string('codigo', 20)->unique();
            $table->string('plan', 50)->nullable();
            $table->string('area', 50)->nullable();
            // CU08 — Plazas disponibles (aforo de cupos a nivel del software).
            $table->integer('cupos')->default(0);
        });

        // CHECK (cupos >= 0): no se permiten plazas negativas.
        DB::statement('ALTER TABLE carrera ADD CONSTRAINT carrera_cupos_check CHECK (cupos >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera');
    }
};
