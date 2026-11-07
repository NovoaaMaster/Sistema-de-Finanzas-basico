<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Series de comprobantes electrónicos y el último número usado de cada una.
        Schema::create('series_electronicas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_doc', 2); // 01 factura, 03 boleta, 07 nota de crédito
            $table->string('serie', 4)->unique();
            $table->unsignedInteger('correlativo')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_electronicas');
    }
};
