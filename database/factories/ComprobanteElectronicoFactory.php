<?php

namespace Database\Factories;

use App\Models\ComprobanteElectronico;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComprobanteElectronico> */
class ComprobanteElectronicoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pclap_venta_id' => fake()->unique()->numberBetween(100000, 999999), // lejos de los ids de las ventas de prueba
            'tipo_doc' => '03',
            'serie' => 'B001',
            'correlativo' => fake()->unique()->numberBetween(1, 99999),
            'fecha_emision' => '2026-11-06 10:30:00',
            'cliente_tipo_doc' => '-',
            'cliente_num_doc' => '-',
            'cliente_razon_social' => 'Público General',
            'moneda' => 'PEN',
            'op_gravadas' => 122.03,
            'igv' => 21.97,
            'total' => 144.00,
            'estado' => 'ACEPTADO',
        ];
    }

    public function factura(): static
    {
        return $this->state([
            'tipo_doc' => '01',
            'serie' => 'F001',
            'cliente_tipo_doc' => '6',
            'cliente_num_doc' => '20512345678',
            'cliente_razon_social' => 'Comercial Andina SAC',
        ]);
    }

    public function estado(string $estado): static
    {
        return $this->state(['estado' => $estado]);
    }

    /** Agrega una línea por 144.00 (placa h510) para que el comprobante tenga detalle. */
    public function conDetalle(): static
    {
        return $this->afterCreating(fn (ComprobanteElectronico $comprobante) => $comprobante->detalles()->create([
            'codigo' => '000403037473',
            'descripcion' => 'placa h510 - S/N: 515151',
            'unidad' => 'NIU',
            'cantidad' => 1,
            'valor_unitario' => 122.033898,
            'precio_unitario' => 144.00,
            'valor_venta' => 122.03,
            'igv' => 21.97,
            'total' => 144.00,
        ]));
    }
}
