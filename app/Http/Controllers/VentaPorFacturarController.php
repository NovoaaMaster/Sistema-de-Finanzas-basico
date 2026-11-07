<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmitirComprobanteRequest;
use App\Pclap\VentaPclap;
use App\Services\Facturacion\FacturacionService;
use App\Services\Facturacion\MapeadorVentaPclap;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class VentaPorFacturarController extends Controller
{
    public function index(Request $request, FacturacionService $facturacion): View
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        try {
            $ventas = $facturacion->ventasPorFacturar($filtros['desde'] ?? null, $filtros['hasta'] ?? null);
            $errorPclap = null;
        } catch (Throwable $error) {
            report($error);
            $ventas = collect();
            $errorPclap = 'No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.';
        }

        return view('ventas.index', compact('ventas', 'filtros', 'errorPclap'));
    }

    /** Datos de la vista previa (modal) antes de emitir. */
    public function borrador(VentaPclap $venta, MapeadorVentaPclap $mapeador): JsonResponse
    {
        return response()->json($mapeador->borrador($venta));
    }

    public function emitir(EmitirComprobanteRequest $request, VentaPclap $venta, FacturacionService $facturacion): JsonResponse
    {
        try {
            $comprobante = $facturacion->emitirDesdeVenta($venta, $request->validated());
        } catch (DomainException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        return response()->json([
            'id' => $comprobante->id,
            'numero' => $comprobante->numero(),
            'tipo' => $comprobante->nombreTipo(),
            'estado' => $comprobante->estado,
            'codigo' => $comprobante->sunat_codigo,
            'mensaje' => $comprobante->sunat_mensaje,
            'pdf' => route('comprobantes.pdf', $comprobante),
        ]);
    }
}
