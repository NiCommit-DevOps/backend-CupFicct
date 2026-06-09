<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestion', function (Blueprint $table) {
            $table->id('id_gestion');
            $table->string('nombre', 50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('ACTIVA');
        });

        // CHECK del estado (paridad con el esquema físico CUPBD.sql).
        DB::statement("ALTER TABLE gestion ADD CONSTRAINT gestion_estado_check CHECK (estado IN ('ACTIVA', 'CERRADA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('gestion');
    }
};
