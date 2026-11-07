<?php

namespace App\Services\Facturacion;

use App\Pclap\VentaPclap;
use Illuminate\Support\Str;

/**
 * Convierte una venta de PCLAP en el borrador de un comprobante electrónico:
 * tipo sugerido, cliente, líneas con sus números de serie y montos con el IGV separado.
 */
class MapeadorVentaPclap
{
    public function __construct(private CalculadoraIgv $calculadora) {}

    public function borrador(VentaPclap $venta): array
    {
        $venta->loadMissing(['cliente.persona.documento', 'tipoComprobante', 'productos', 'series']);

        $cliente = $this->cliente($venta);
        $lineas = $this->lineas($venta);
        $totales = $this->calculadora->totales($lineas);
        $totalPclap = round((float) $venta->total, 2);

        return [
            'pclap_venta_id' => $venta->id,
            'numero_pclap' => $venta->numero_comprobante,
            'tipo_pclap' => $venta->tipoComprobante?->nombre,
            'fecha_pclap' => $venta->fecha_hora?->format('d/m/Y H:i'),
            'tipo_doc' => $cliente['tipo_doc'] === '6' || $venta->tipoComprobante?->nombre === 'Factura' ? '01' : '03',
            'cliente' => $cliente,
            'lineas' => $lineas,
            'totales' => $totales,
            'total_pclap' => $totalPclap,
            'cuadra' => $totales['total'] === $totalPclap,
        ];
    }

    /** Cliente con su tipo de documento del catálogo 06 de SUNAT: 6 RUC, 1 DNI, "-" varios. */
    private function cliente(VentaPclap $venta): array
    {
        $persona = $venta->cliente?->persona;
        $numero = trim((string) $persona?->numero_documento);
        $documento = $persona?->documento?->nombre;
        $razonSocial = filled($venta->cliente_nuevo) ? trim($venta->cliente_nuevo) : (string) $persona?->razon_social;

        [$tipo, $num] = match (true) {
            $documento === 'RUC' && preg_match('/^\d{11}$/', $numero) === 1 => ['6', $numero],
            $documento === 'DNI' && preg_match('/^\d{8}$/', $numero) === 1 && $numero !== '00000000' && blank($venta->cliente_nuevo) => ['1', $numero],
            default => ['-', '-'],
        };

        return ['tipo_doc' => $tipo, 'num_doc' => $num, 'razon_social' => $razonSocial];
    }

    private function lineas(VentaPclap $venta): array
    {
        $seriesPorProducto = $venta->series->groupBy('producto_id');

        return $venta->productos->map(function ($producto) use ($seriesPorProducto) {
            $series = $seriesPorProducto->get($producto->id, collect())->pluck('numero_serie');
            $descripcion = $series->isEmpty() ? $producto->nombre : $producto->nombre.' - S/N: '.$series->implode(', ');
            $cantidad = (float) $producto->pivot->cantidad;

            return [
                'codigo' => $producto->codigo,
                'descripcion' => Str::limit($descripcion, 500, ''),
                'unidad' => 'NIU',
                'cantidad' => $cantidad,
                ...$this->calculadora->linea((float) $producto->pivot->precio_venta, $cantidad),
            ];
        })->values()->all();
    }
}
