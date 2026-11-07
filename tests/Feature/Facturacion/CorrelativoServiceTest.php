<?php

namespace Tests\Feature\Facturacion;

use App\Models\SerieElectronica;
use App\Services\Facturacion\CorrelativoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrelativoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_entrega_numeros_seguidos_por_serie(): void
    {
        SerieElectronica::create(['tipo_doc' => '03', 'serie' => 'B001', 'correlativo' => 0]);
        SerieElectronica::create(['tipo_doc' => '01', 'serie' => 'F001', 'correlativo' => 41]);
        $servicio = app(CorrelativoService::class);

        $this->assertSame(1, $servicio->siguiente('B001'));
        $this->assertSame(2, $servicio->siguiente('B001'));
        $this->assertSame(42, $servicio->siguiente('F001'));
        $this->assertSame(2, SerieElectronica::where('serie', 'B001')->value('correlativo'));
    }

    public function test_serie_inexistente_falla(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(CorrelativoService::class)->siguiente('X999');
    }

    public function test_elige_la_serie_segun_el_tipo_y_el_comprobante_de_referencia(): void
    {
        SerieElectronica::create(['tipo_doc' => '01', 'serie' => 'F001']);
        SerieElectronica::create(['tipo_doc' => '03', 'serie' => 'B001']);
        SerieElectronica::create(['tipo_doc' => '07', 'serie' => 'FC01']);
        SerieElectronica::create(['tipo_doc' => '07', 'serie' => 'BC01']);
        $servicio = app(CorrelativoService::class);

        $this->assertSame('F001', $servicio->seriePara('01'));
        $this->assertSame('B001', $servicio->seriePara('03'));
        $this->assertSame('FC01', $servicio->seriePara('07', '01'));
        $this->assertSame('BC01', $servicio->seriePara('07', '03'));
    }
}
