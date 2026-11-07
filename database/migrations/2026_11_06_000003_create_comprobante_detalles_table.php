<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_electronico_id')->constrained('comprobantes_electronicos')->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('descripcion', 500);
            $table->string('unidad', 3)->default('NIU');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('valor_unitario', 14, 6); // sin IGV
            $table->decimal('precio_unitario', 12, 2); // con IGV
            $table->decimal('valor_venta', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_detalles');
    }
};
