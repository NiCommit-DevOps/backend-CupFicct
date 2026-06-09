<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU04 — Datos adicionales del postulante requeridos por la ficha de inscripción:
 * Dirección y "Otros" (observaciones libres). No están en el esquema base
 * (CUPBD.sql); se agregan como columnas nullable, igual que el ajuste de `cupos`
 * en carrera. Ciudad → `procedencia`, Colegio → `id_unidad` (ya existían).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->string('direccion', 150)->nullable()->after('procedencia');
            $table->string('otros', 255)->nullable()->after('anio_egreso');
        });
    }

    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'otros']);
        });
    }
};
