<?php

namespace Tests\Feature\Pantallas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class AvisosTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    public function test_los_avisos_muestran_el_mensaje_como_texto_y_no_como_html(): void
    {
        $this->crearEsquemaPclap();
        $mensaje = 'La venta <img src=x onerror=alert(1)> ya tiene un comprobante electrónico.';

        $html = $this->actingAs(User::factory()->create())
            ->withSession(['error' => $mensaje])
            ->get('/')
            ->getContent();

        // SweetAlert2 interpreta "title" como HTML; "titleText" lo muestra como texto plano.
        $this->assertStringContainsString('titleText: '.json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), $html);
        $this->assertStringNotContainsString('title: '.json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), $html);
    }
}
