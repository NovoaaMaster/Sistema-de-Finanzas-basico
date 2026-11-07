<?php

namespace App\Http\Controllers;

use App\Models\SerieElectronica;
use App\Pclap\VentaPclap;
use Illuminate\View\View;
use Throwable;

/** Solo lectura: los datos se cambian en el .env y en las series de la base de Finanzas. */
class ConfiguracionController extends Controller
{
    public function __invoke(): View
    {
        try {
            $estadoPclap = 'Conectado: '.VentaPclap::count().' ventas en '.config('database.connections.pclap.database');
        } catch (Throwable $error) {
            report($error);
            $estadoPclap = null;
        }

        return view('configuracion', [
            'ruc' => config('sunat.ruc'),
            'emisor' => config('sunat.emisor'),
            'modo' => config('sunat.modo'),
            'series' => SerieElectronica::orderBy('tipo_doc')->orderBy('serie')->get(),
            'estadoPclap' => $estadoPclap,
        ]);
    }
}
