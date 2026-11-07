<?php

namespace Tests\Feature\Facturacion;

use App\Pclap\VentaPclap;
use App\Services\Facturacion\MapeadorVentaPclap;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class MapeadorVentaPclapTest extends TestCase
{
    use ConEsquemaPclap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
    }

    private function borrador(array $opciones = []): array
    {
        return app(MapeadorVentaPclap::class)->borrador(VentaPclap::findOrFail($this->crearVentaPclap($opciones)));
    }

    public function test_venta_a_publico_general_sugiere_boleta_a_clientes_varios(): void
    {
        $borrador = $this->borrador();

        $this->assertSame('03', $borrador['tipo_doc']);
        $this->assertSame(['tipo_doc' => '-', 'num_doc' => '-', 'razon_social' => 'Público General'], $borrador['cliente']);
    }

    public function test_el_nombre_escrito_en_la_venta_reemplaza_a_publico_general(): void
    {
        $borrador = $this->borrador(['cliente_nuevo' => 'Rosa Quispe']);

        $this->assertSame('Rosa Quispe', $borrador['cliente']['razon_social']);
        $this->assertSame('-', $borrador['cliente']['tipo_doc']);
    }

    public function test_cliente_con_ruc_sugiere_factura(): void
    {
        $borrador = $this->borrador([
            'cliente' => ['razon_social' => 'Comercial Andina SAC', 'documento' => 'RUC', 'numero' => '20512345678'],
        ]);

        $this->assertSame('01', $borrador['tipo_doc']);
        $this->assertSame(['tipo_doc' => '6', 'num_doc' => '20512345678', 'razon_social' => 'Comercial Andina SAC'], $borrador['cliente']);
    }

    public function test_cliente_con_dni_real_lo_identifica_con_tipo_1(): void
    {
        $borrador = $this->borrador([
            'cliente' => ['razon_social' => 'Luis Rojas', 'documento' => 'DNI', 'numero' => '45871236'],
        ]);

        $this->assertSame('03', $borrador['tipo_doc']);
        $this->assertSame(['tipo_doc' => '1', 'num_doc' => '45871236', 'razon_social' => 'Luis Rojas'], $borrador['cliente']);
    }

    public function test_si_pclap_la_registro_como_factura_sugiere_factura_aunque_falte_el_ruc(): void
    {
        $this->assertSame('01', $this->borrador(['comprobante' => 'Factura'])['tipo_doc']);
    }

    public function test_cada_linea_lleva_sus_numeros_de_serie_y_el_igv_separado(): void
    {
        $borrador = $this->borrador(['lineas' => [
            ['producto' => 'ssd de 1tb kingston', 'codigo' => '006673057148', 'cantidad' => 4, 'precio' => 306.00, 'series' => ['0003924387', 'rrrr']],
            ['producto' => 'cable hdmi', 'codigo' => null, 'cantidad' => 1, 'precio' => 20.00],
        ]]);

        $this->assertCount(2, $borrador['lineas']);
        $this->assertSame('ssd de 1tb kingston - S/N: 0003924387, rrrr', $borrador['lineas'][0]['descripcion']);
        $this->assertSame('006673057148', $borrador['lineas'][0]['codigo']);
        $this->assertEquals(4, $borrador['lineas'][0]['cantidad']);
        $this->assertSame(1037.29, $borrador['lineas'][0]['valor_venta']);
        $this->assertSame(186.71, $borrador['lineas'][0]['igv']);
        $this->assertSame('cable hdmi', $borrador['lineas'][1]['descripcion']);

        $this->assertSame(['op_gravadas' => 1054.24, 'igv' => 189.76, 'total' => 1244.00], $borrador['totales']);
        $this->assertTrue($borrador['cuadra']);
    }

    public function test_avisa_si_el_total_no_cuadra_con_el_de_pclap(): void
    {
        $borrador = $this->borrador(['total' => 150.00]);

        $this->assertFalse($borrador['cuadra']);
        $this->assertSame(150.00, $borrador['total_pclap']);
    }
}
