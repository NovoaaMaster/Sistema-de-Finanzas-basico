<?php

namespace App\Services\Facturacion;

/**
 * Separa el IGV de precios que ya lo incluyen (precio al público de PCLAP).
 * Se calcula en céntimos para que valor de venta + IGV cuadre siempre con el total.
 */
class CalculadoraIgv
{
    public function __construct(private float $tasa) {}

    /** @return array{precio_unitario: float, valor_unitario: float, valor_venta: float, igv: float, total: float} */
    public function linea(float $precioConIgv, float $cantidad): array
    {
        $totalCentimos = (int) round($precioConIgv * $cantidad * 100);
        $valorCentimos = (int) round($totalCentimos / (1 + $this->tasa));

        return [
            'precio_unitario' => round($precioConIgv, 2),
            'valor_unitario' => round($precioConIgv / (1 + $this->tasa), 6),
            'valor_venta' => round($valorCentimos / 100, 2),
            'igv' => round(($totalCentimos - $valorCentimos) / 100, 2),
            'total' => round($totalCentimos / 100, 2),
        ];
    }

    /** @return array{op_gravadas: float, igv: float, total: float} */
    public function totales(array $lineas): array
    {
        $suma = fn (string $campo) => round(array_sum(array_map(fn ($linea) => (int) round($linea[$campo] * 100), $lineas)) / 100, 2);

        return [
            'op_gravadas' => $suma('valor_venta'),
            'igv' => $suma('igv'),
            'total' => $suma('total'),
        ];
    }
}
