<?php

namespace Tests\Feature\Facturacion;

use App\Models\ComprobanteElectronico;
use App\Pclap\VentaPclap;
use App\Services\Facturacion\FacturacionService;
use App\Services\Facturacion\ServicioSunat;
use Database\Seeders\SerieElectronicaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ConEsquemaPclap;
use Tests\Fakes\ServicioSunatFalso;
use Tests\TestCase;

class FacturacionServiceTest extends TestCase
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
        Carbon::setTestNow('2026-11-06 10:30:00');
    }

    private function servicio(): FacturacionService
    {
        return app(FacturacionService::class);
    }

    private function clienteVarios(string $tipoDoc = '03'): array
    {
        return ['tipo_doc' => $tipoDoc, 'cliente_tipo_doc' => '-', 'cliente_num_doc' => '-', 'cliente_razon_social' => 'Público General'];
    }

    public function test_emite_una_boleta_desde_la_venta_de_pclap(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());

        $boleta = $this->servicio()->emitirDesdeVenta($venta, $this->clienteVarios());

        $this->assertSame('ACEPTADO', $boleta->estado);
        $this->assertSame('B001-1', $boleta->numero());
        $this->assertSame($venta->id, $boleta->pclap_venta_id);
        $this->assertSame('2026-11-06 10:30:00', $boleta->fecha_emision->format('Y-m-d H:i:s'));
        $this->assertSame('144.00', $boleta->total);
        $this->assertSame('21.97', $boleta->igv);
        $this->assertSame('placa h510 - S/N: 515151', $boleta->detalles()->value('descripcion'));
        $this->assertCount(1, $this->sunat->enviados);
        $this->assertSame('PEN', $this->sunat->enviados[0]->getTipoMoneda());
    }

    public function test_la_factura_usa_la_serie_f_y_los_datos_de_cliente_corregidos(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap(['comprobante' => 'Factura']));

        $factura = $this->servicio()->emitirDesdeVenta($venta, [
            'tipo_doc' => '01',
            'cliente_tipo_doc' => '6',
            'cliente_num_doc' => '20512345678',
            'cliente_razon_social' => 'Comercial Andina SAC',
        ]);

        $this->assertSame('F001-1', $factura->numero());
        $this->assertSame('20512345678', $factura->cliente_num_doc);
        $this->assertSame('Comercial Andina SAC', $factura->cliente_razon_social);
    }

    public function test_no_emite_dos_veces_la_misma_venta(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());
        $this->servicio()->emitirDesdeVenta($venta, $this->clienteVarios());

        $this->expectException(DomainException::class);
        $this->servicio()->emitirDesdeVenta($venta, $this->clienteVarios());
    }

    public function test_si_sunat_rechazo_el_comprobante_la_venta_se_puede_volver_a_emitir(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());
        $this->sunat->rechazarConCdr('2800', 'Documento del receptor inválido');
        $this->servicio()->emitirDesdeVenta($venta, $this->clienteVarios());

        $this->sunat->aceptar();
        $nueva = $this->servicio()->emitirDesdeVenta($venta, $this->clienteVarios());

        $this->assertSame('B001-2', $nueva->numero());
        $this->assertSame('ACEPTADO', $nueva->estado);
    }

    public function test_ventas_por_facturar_son_las_pagadas_sin_comprobante_vigente(): void
    {
        $sinFacturar = $this->crearVentaPclap();
        $facturada = $this->crearVentaPclap();
        $rechazada = $this->crearVentaPclap();
        $this->crearVentaPclap(['estado_pago' => 'PENDIENTE']);
        $this->crearVentaPclap(['estado_pago' => 'DEVUELTO']);
        ComprobanteElectronico::factory()->create(['pclap_venta_id' => $facturada]);
        ComprobanteElectronico::factory()->estado('RECHAZADO')->create(['pclap_venta_id' => $rechazada]);

        $ids = $this->servicio()->ventasPorFacturar()->pluck('id')->sort()->values()->all();

        $this->assertSame([$sinFacturar, $rechazada], $ids);
    }

    public function test_ventas_por_facturar_se_filtran_por_fecha(): void
    {
        $this->crearVentaPclap(['fecha_hora' => '2026-09-16 21:44:38']);
        $octubre = $this->crearVentaPclap(['fecha_hora' => '2026-10-24 18:57:03']);

        $ids = $this->servicio()->ventasPorFacturar('2026-10-01', '2026-10-31')->pluck('id')->all();

        $this->assertSame([$octubre], $ids);
    }

    public function test_nota_de_credito_copia_lineas_y_usa_la_serie_de_la_boleta(): void
    {
        $boleta = $this->servicio()->emitirDesdeVenta(VentaPclap::findOrFail($this->crearVentaPclap()), $this->clienteVarios());

        $nota = $this->servicio()->emitirNotaCredito($boleta, '07', 'Devolución del producto');

        $this->assertSame('BC01-1', $nota->numero());
        $this->assertSame('07', $nota->tipo_doc);
        $this->assertSame($boleta->id, $nota->comprobante_referencia_id);
        $this->assertSame('Devolución del producto', $nota->motivo_descripcion);
        $this->assertSame('144.00', $nota->total);
        $this->assertSame(1, $nota->detalles()->count());
        $this->assertSame('ACEPTADO', $nota->estado);
        $this->assertFalse($boleta->fresh()->admiteNotaCredito());
    }

    public function test_no_emite_nota_de_credito_sobre_un_comprobante_rechazado(): void
    {
        $rechazada = ComprobanteElectronico::factory()->estado('RECHAZADO')->conDetalle()->create();

        $this->expectException(DomainException::class);
        $this->servicio()->emitirNotaCredito($rechazada, '01', 'Anulación de la operación');
    }

    public function test_reenvia_con_el_mismo_numero_lo_que_quedo_en_error(): void
    {
        $this->sunat->fallar('HTTP', 'Unauthorized');
        $boleta = $this->servicio()->emitirDesdeVenta(VentaPclap::findOrFail($this->crearVentaPclap()), $this->clienteVarios());

        $this->sunat->aceptar();
        $reenviada = $this->servicio()->reenviar($boleta);

        $this->assertSame('B001-1', $reenviada->numero());
        $this->assertSame('ACEPTADO', $reenviada->estado);
    }

    public function test_no_reenvia_lo_que_ya_fue_aceptado(): void
    {
        $aceptada = ComprobanteElectronico::factory()->conDetalle()->create();

        $this->expectException(DomainException::class);
        $this->servicio()->reenviar($aceptada);
    }
}
