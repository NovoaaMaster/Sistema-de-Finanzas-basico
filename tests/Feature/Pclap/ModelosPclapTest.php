<?php

namespace Tests\Feature\Pclap;

use App\Pclap\VentaPclap;
use LogicException;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class ModelosPclapTest extends TestCase
{
    use ConEsquemaPclap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
    }

    public function test_lee_la_venta_con_su_cliente_productos_y_series(): void
    {
        $id = $this->crearVentaPclap([
            'cliente' => ['razon_social' => 'Comercial Andina SAC', 'documento' => 'RUC', 'numero' => '20512345678'],
            'lineas' => [['producto' => 'ssd de 1tb kingston', 'codigo' => '006673057148', 'cantidad' => 2, 'precio' => 306.00, 'series' => ['A1', 'A2']]],
        ]);

        $venta = VentaPclap::with(['cliente.persona.documento', 'tipoComprobante', 'productos', 'series'])->findOrFail($id);

        $this->assertSame('Comercial Andina SAC', $venta->cliente->persona->razon_social);
        $this->assertSame('RUC', $venta->cliente->persona->documento->nombre);
        $this->assertSame('Boleta', $venta->tipoComprobante->nombre);
        $this->assertSame('ssd de 1tb kingston', $venta->productos->first()->nombre);
        $this->assertEquals(2, $venta->productos->first()->pivot->cantidad);
        $this->assertEquals(['A1', 'A2'], $venta->series->pluck('numero_serie')->all());
    }

    public function test_no_permite_guardar_cambios_en_pclap(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());
        $venta->total = 1;

        $this->expectException(LogicException::class);
        $venta->save();
    }

    public function test_no_permite_borrar_en_pclap(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());

        $this->expectException(LogicException::class);
        $venta->delete();
    }

    public function test_pagadas_excluye_notas_pendientes_y_devueltas(): void
    {
        $pagada = $this->crearVentaPclap();
        $this->crearVentaPclap(['estado_pago' => 'PENDIENTE']);
        $this->crearVentaPclap(['estado_pago' => 'DEVUELTO']);

        $this->assertSame([$pagada], VentaPclap::pagadas()->pluck('id')->all());
    }
}
