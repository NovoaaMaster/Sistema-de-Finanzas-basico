<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Pclap\VentaPclap;
use App\Services\Facturacion\FacturacionService;
use App\Services\Facturacion\RepresentacionImpresa;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ComprobanteController extends Controller
{
    public function index(): View
    {
        $comprobantes = ComprobanteElectronico::with('referencia')->latest('id')->paginate(20);

        return view('comprobantes.index', [
            'comprobantes' => $comprobantes,
            'devueltasEnPclap' => $this->devueltasEnPclap($comprobantes->pluck('pclap_venta_id')->filter()->all()),
        ]);
    }

    public function pdf(ComprobanteElectronico $comprobante, RepresentacionImpresa $impresa): Response
    {
        return $impresa->pdf($comprobante);
    }

    public function xml(ComprobanteElectronico $comprobante): Response
    {
        return $this->descargar($comprobante->xml_path);
    }

    public function cdr(ComprobanteElectronico $comprobante): Response
    {
        return $this->descargar($comprobante->cdr_path);
    }

    public function reenviar(ComprobanteElectronico $comprobante, FacturacionService $facturacion): RedirectResponse
    {
        try {
            $comprobante = $facturacion->reenviar($comprobante);
        } catch (DomainException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route('comprobantes.index')->with(
            in_array($comprobante->estado, ['ACEPTADO', 'OBSERVADO'], true) ? 'success' : 'error',
            "{$comprobante->numero()}: {$comprobante->estado}. {$comprobante->sunat_mensaje}",
        );
    }

    private function descargar(?string $ruta): Response
    {
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta);
    }

    /** Ids de ventas que en PCLAP figuran como devueltas: sus comprobantes deberían tener nota de crédito. */
    private function devueltasEnPclap(array $ventaIds): array
    {
        if (! $ventaIds) {
            return [];
        }

        try {
            return VentaPclap::whereIn('id', $ventaIds)->where('estado_pago', 'DEVUELTO')->pluck('id')->map(fn ($id) => (int) $id)->all();
        } catch (Throwable $error) {
            report($error);

            return [];
        }
    }
}
