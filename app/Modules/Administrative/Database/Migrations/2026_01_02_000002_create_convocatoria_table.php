<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria', function (Blueprint $table) {
            $table->id('id_convocatoria');
            $table->foreignId('id_gestion')
                ->constrained('gestion', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre', 50);
            $table->date('fecha_creacion')->useCurrent();
            $table->date('fecha_limite_inscripcion');
            $table->string('estado', 20)->default('ABIERTA');
        });

        // CHECK del estado (paridad con el esquema físico CUPBD.sql).
        DB::statement("ALTER TABLE convocatoria ADD CONSTRAINT convocatoria_estado_check CHECK (estado IN ('ABIERTA', 'PROCESO_EVALUACION', 'CONCLUIDA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria');
    }
};
