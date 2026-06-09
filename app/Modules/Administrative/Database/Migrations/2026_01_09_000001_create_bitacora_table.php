<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU11 — Auditoría forense: registro indeleble de mutaciones de datos críticos.
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id('id_bitacora');
            $table->string('tabla', 100);
            $table->string('operacion', 10); // INSERT / UPDATE / DELETE
            $table->string('registro_id', 100)->nullable(); // PK del registro afectado
            // Estado antes/después en JSONB (consultable y comparable).
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();
            // Operador responsable (nullable: acciones públicas o de sistema).
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->foreign('id_usuario')
                ->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->string('ip_origen', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha')->useCurrent();

            $table->index(['tabla', 'operacion']);
            $table->index('fecha');
        });

        DB::statement("ALTER TABLE bitacora ADD CONSTRAINT bitacora_operacion_check CHECK (operacion IN ('INSERT', 'UPDATE', 'DELETE'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
