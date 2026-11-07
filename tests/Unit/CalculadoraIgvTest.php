<?php

namespace Tests\Unit;

use App\Services\Facturacion\CalculadoraIgv;
use PHPUnit\Framework\TestCase;

class CalculadoraIgvTest extends TestCase
{
    public function test_separa_el_igv_de_un_precio_que_ya_lo_incluye(): void
    {
        $linea = (new CalculadoraIgv(0.18))->linea(144.00, 1);

        $this->assertSame(122.03, $linea['valor_venta']);
        $this->assertSame(21.97, $linea['igv']);
        $this->assertSame(144.00, $linea['total']);
        $this->assertSame(144.00, $linea['precio_unitario']);
        $this->assertSame(122.033898, $linea['valor_unitario']);
    }

    public function test_la_linea_multiplica_por_la_cantidad_antes_de_redondear(): void
    {
        $linea = (new CalculadoraIgv(0.18))->linea(306.00, 4);

        $this->assertSame(1224.00, $linea['total']);
        $this->assertSame(1037.29, $linea['valor_venta']);
        $this->assertSame(186.71, $linea['igv']);
        $this->assertSame(259.322034, $linea['valor_unitario']);
    }

    public function test_los_totales_suman_las_lineas_y_cuadran_al_centimo(): void
    {
        $calculadora = new CalculadoraIgv(0.18);

        $totales = $calculadora->totales([
            $calculadora->linea(170.00, 2),
            $calculadora->linea(0.10, 3),
        ]);

        $this->assertSame(288.39, $totales['op_gravadas']);
        $this->assertSame(51.91, $totales['igv']);
        $this->assertSame(340.30, $totales['total']);
        $this->assertSame($totales['total'], round($totales['op_gravadas'] + $totales['igv'], 2));
    }
}
