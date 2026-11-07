<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_electronicos', function (Blueprint $table) {
            $table->id();
            // Venta de PCLAP de la que sale el comprobante (otra base de datos: sin clave foránea).
            $table->unsignedBigInteger('pclap_venta_id')->nullable()->index();
            $table->string('tipo_doc', 2);
            $table->string('serie', 4);
            $table->unsignedInteger('correlativo');
            $table->dateTime('fecha_emision');

            $table->string('cliente_tipo_doc', 1);
            $table->string('cliente_num_doc', 15);
            $table->string('cliente_razon_social');

            $table->string('moneda', 3)->default('PEN');
            $table->decimal('op_gravadas', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);

            // PENDIENTE, ACEPTADO, OBSERVADO, RECHAZADO o ERROR (no llegó a SUNAT: se puede reenviar).
            $table->string('estado', 12)->default('PENDIENTE');
            $table->string('sunat_codigo', 10)->nullable();
            $table->text('sunat_mensaje')->nullable();
            $table->string('hash')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();

            // Solo notas de crédito: comprobante que modifican y motivo (catálogo 09 de SUNAT).
            $table->foreignId('comprobante_referencia_id')->nullable()->constrained('comprobantes_electronicos');
            $table->string('motivo_codigo', 2)->nullable();
            $table->string('motivo_descripcion')->nullable();

            $table->timestamps();

            $table->unique(['serie', 'correlativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_electronicos');
    }
};
