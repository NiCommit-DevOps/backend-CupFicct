<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CU05 — Transacción financiera del cupo de inscripción (derecho a examen).
        Schema::create('pago', function (Blueprint $table) {
            $table->id('id_pago');
            $table->foreignId('id_inscripcion')
                ->constrained('inscripcion', 'id_inscripcion')->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 10)->default('BOB');
            $table->string('estado', 20)->default('PENDIENTE');
            $table->string('metodo', 30)->default('PAYPAL');
            // ID único emitido por la pasarela; UNIQUE evita procesar dos veces el
            // mismo comprobante bancario.
            $table->string('transaccion_id', 255)->nullable()->unique();
            // Hash de control interno (HMAC) que firma monto + transacción para
            // detectar alteraciones maliciosas del registro.
            $table->string('seguridad_hash', 255)->nullable();
            $table->timestamp('fecha')->nullable();
        });

        DB::statement("ALTER TABLE pago ADD CONSTRAINT pago_estado_check CHECK (estado IN ('PENDIENTE', 'APROBADO', 'RECHAZADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
