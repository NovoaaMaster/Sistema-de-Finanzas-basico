<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Services\Facturacion\FacturacionService;
use Illuminate\View\View;
use Throwable;

class PanelController extends Controller
{
    public function __invoke(FacturacionService $facturacion): View
    {
        try {
            $porFacturar = $facturacion->ventasPorFacturar()->count();
            $errorPclap = null;
        } catch (Throwable $error) {
            report($error);
            $porFacturar = null;
            $errorPclap = 'No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.';
        }

        return view('panel', [
            'porFacturar' => $porFacturar,
            'errorPclap' => $errorPclap,
            'aceptadosHoy' => ComprobanteElectronico::whereDate('fecha_emision', today())
                ->whereIn('estado', ['ACEPTADO', 'OBSERVADO'])
                ->count(),
            'conProblemas' => ComprobanteElectronico::whereIn('estado', ['RECHAZADO', 'ERROR'])->count(),
            'ultimos' => ComprobanteElectronico::latest('id')->limit(5)->get(),
        ]);
    }
}
