<?php

namespace Tests\Feature\Pantallas;

use App\Models\ComprobanteElectronico;
use App\Models\User;
use App\Services\Facturacion\ServicioSunat;
use Database\Seeders\SerieElectronicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ConEsquemaPclap;
use Tests\Fakes\ServicioSunatFalso;
use Tests\TestCase;

class VentasPorFacturarTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    private ServicioSunatFalso $sunat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        $this->seed(SerieElectronicaSeeder::class);
        Storage::fake('local');
        $this->sunat = new ServicioSunatFalso;
        $this->app->instance(ServicioSunat::class, $this->sunat);
        $this->actingAs(User::factory()->create());
    }

    private function datos(array $cambios = []): array
    {
        return [
            'tipo_doc' => '03',
            'cliente_tipo_doc' => '-',
            'cliente_num_doc' => '-',
            'cliente_razon_social' => 'Público General',
            ...$cambios,
        ];
    }

    public function test_lista_las_ventas_pagadas_de_pclap_sin_comprobante(): void
    {
        $this->crearVentaPclap(['cliente_nuevo' => 'Rosa Quispe']);
        $this->crearVentaPclap(['cliente_nuevo' => 'Nota Pendiente', 'estado_pago' => 'PENDIENTE']);

        $this->get('/ventas-por-facturar')
            ->assertOk()
            ->assertSee('Rosa Quispe')
            ->assertSee('144.00')
            ->assertDontSee('Nota Pendiente');
    }

    public function test_el_borrador_trae_cliente_lineas_y_totales_para_la_vista_previa(): void
    {
        $venta = $this->crearVentaPclap();

        $this->getJson("/ventas-por-facturar/{$venta}/borrador")
            ->assertOk()
            ->assertJsonPath('tipo_doc', '03')
            ->assertJsonPath('cliente.tipo_doc', '-')
            ->assertJsonPath('lineas.0.descripcion', 'placa h510 - S/N: 515151')
            ->assertJsonPath('totales.igv', 21.97)
            ->assertJsonPath('cuadra', true);
    }

    public function test_emite_la_boleta_y_responde_con_la_respuesta_de_sunat(): void
    {
        $venta = $this->crearVentaPclap();

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos())
            ->assertOk()
            ->assertJsonPath('numero', 'B001-1')
            ->assertJsonPath('estado', 'ACEPTADO')
            ->assertJsonPath('mensaje', 'El comprobante ha sido aceptado');

        $this->assertSame(1, ComprobanteElectronico::count());
    }

    public function test_la_factura_exige_el_ruc_del_cliente(): void
    {
        $venta = $this->crearVentaPclap(['comprobante' => 'Factura']);

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos(['tipo_doc' => '01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_tipo_doc');

        $this->assertSame(0, ComprobanteElectronico::count());
    }

    public function test_el_ruc_debe_tener_11_digitos_y_el_dni_8(): void
    {
        $venta = $this->crearVentaPclap();

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos(['tipo_doc' => '01', 'cliente_tipo_doc' => '6', 'cliente_num_doc' => '2051234']))
            ->assertJsonValidationErrors('cliente_num_doc');
        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos(['cliente_tipo_doc' => '1', 'cliente_num_doc' => '4587']))
            ->assertJsonValidationErrors('cliente_num_doc');
    }

    public function test_boleta_de_mas_de_700_soles_debe_identificar_al_cliente(): void
    {
        $venta = $this->crearVentaPclap(['lineas' => [['producto' => 'procesador', 'cantidad' => 1, 'precio' => 2000.00]]]);

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos())
            ->assertJsonValidationErrors('cliente_tipo_doc');

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos(['cliente_tipo_doc' => '1', 'cliente_num_doc' => '45871236', 'cliente_razon_social' => 'Luis Rojas']))
            ->assertOk()
            ->assertJsonPath('estado', 'ACEPTADO');
    }

    public function test_no_emite_dos_veces_la_misma_venta(): void
    {
        $venta = $this->crearVentaPclap();
        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos())->assertOk();

        $this->postJson("/ventas-por-facturar/{$venta}/emitir", $this->datos())
            ->assertUnprocessable()
            ->assertJsonPath('message', fn ($mensaje) => str_contains($mensaje, 'ya tiene un comprobante'));
    }
}
