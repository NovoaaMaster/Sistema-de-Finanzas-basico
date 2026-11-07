<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Services\Facturacion\FacturacionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotaCreditoController extends Controller
{
    public function create(ComprobanteElectronico $comprobante): View|RedirectResponse
    {
        if (! $comprobante->admiteNotaCredito()) {
            return redirect()->route('comprobantes.index')
                ->with('error', "El comprobante {$comprobante->numero()} no admite nota de crédito: debe estar aceptado y no tener otra nota.");
        }

        return view('notas.create', [
            'comprobante' => $comprobante->load('detalles'),
            'motivos' => ComprobanteElectronico::MOTIVOS_NOTA_CREDITO,
        ]);
    }

    public function store(Request $request, ComprobanteElectronico $comprobante, FacturacionService $facturacion): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_codigo' => ['required', Rule::in(array_keys(ComprobanteElectronico::MOTIVOS_NOTA_CREDITO))],
            'motivo_descripcion' => ['required', 'string', 'max:250'],
        ]);

        try {
            $nota = $facturacion->emitirNotaCredito($comprobante, $datos['motivo_codigo'], $datos['motivo_descripcion']);
        } catch (DomainException $error) {
            return redirect()->route('comprobantes.index')->with('error', $error->getMessage());
        }

        return redirect()->route('comprobantes.index')->with(
            in_array($nota->estado, ['ACEPTADO', 'OBSERVADO'], true) ? 'success' : 'error',
            "Nota de crédito {$nota->numero()}: {$nota->estado}. {$nota->sunat_mensaje}",
        );
    }
}
