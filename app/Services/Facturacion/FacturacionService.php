<?php

namespace App\Services\Facturacion;

use App\Models\ComprobanteElectronico;
use App\Pclap\VentaPclap;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Casos de uso de la facturación: qué falta facturar, emitir, notas de crédito y reenvíos. */
class FacturacionService
{
    private const CAMPOS_DETALLE = ['codigo', 'descripcion', 'unidad', 'cantidad', 'valor_unitario', 'precio_unitario', 'valor_venta', 'igv', 'total'];

    public function __construct(
        private MapeadorVentaPclap $mapeador,
        private CorrelativoService $correlativos,
        private EmisorSunat $emisor,
    ) {}

    /** Ventas cobradas en PCLAP sin comprobante vigente (una venta con comprobante rechazado vuelve a la lista). */
    public function ventasPorFacturar(?string $desde = null, ?string $hasta = null): Collection
    {
        $facturadas = ComprobanteElectronico::query()
            ->whereIn('tipo_doc', ['01', '03'])
            ->where('estado', '!=', 'RECHAZADO')
            ->whereNotNull('pclap_venta_id')
            ->pluck('pclap_venta_id');

        return VentaPclap::pagadas()
            ->with(['cliente.persona', 'tipoComprobante'])
            ->whereNotIn('id', $facturadas)
            ->when($desde, fn ($query) => $query->where('fecha_hora', '>=', $desde.' 00:00:00'))
            ->when($hasta, fn ($query) => $query->where('fecha_hora', '<=', $hasta.' 23:59:59'))
            ->orderByDesc('fecha_hora')
            ->get();
    }

    /**
     * @param  array{tipo_doc: string, cliente_tipo_doc: string, cliente_num_doc: string, cliente_razon_social: string}  $datos  ya validados
     */
    public function emitirDesdeVenta(VentaPclap $venta, array $datos): ComprobanteElectronico
    {
        if ($venta->estado_pago !== 'PAGADO') {
            throw new DomainException('Solo se emiten comprobantes de ventas pagadas.');
        }

        $borrador = $this->mapeador->borrador($venta);

        $comprobante = DB::transaction(function () use ($venta, $datos, $borrador) {
            // El número se reserva primero: bloquea la serie y evita que dos clics emitan la misma venta.
            $serie = $this->correlativos->seriePara($datos['tipo_doc']);
            $correlativo = $this->correlativos->siguiente($serie);

            if ($this->tieneComprobanteVigente($venta->id)) {
                throw new DomainException("La venta {$venta->numero_comprobante} ya tiene un comprobante electrónico.");
            }

            $comprobante = ComprobanteElectronico::create([
                'pclap_venta_id' => $venta->id,
                'tipo_doc' => $datos['tipo_doc'],
                'serie' => $serie,
                'correlativo' => $correlativo,
                'fecha_emision' => now(),
                'cliente_tipo_doc' => $datos['cliente_tipo_doc'],
                'cliente_num_doc' => $datos['cliente_num_doc'],
                'cliente_razon_social' => $datos['cliente_razon_social'],
                'moneda' => 'PEN',
                ...$borrador['totales'],
                'estado' => 'PENDIENTE',
            ]);
            $comprobante->detalles()->createMany($borrador['lineas']);

            return $comprobante;
        });

        return $this->emisor->emitir($comprobante);
    }

    /** Motivos del catálogo 09 de SUNAT: 01 anulación de la operación, 07 devolución por ítem, etc. */
    public function emitirNotaCredito(ComprobanteElectronico $referencia, string $motivoCodigo, string $motivoDescripcion): ComprobanteElectronico
    {
        if (! $referencia->admiteNotaCredito()) {
            throw new DomainException("El comprobante {$referencia->numero()} no admite nota de crédito: debe estar aceptado y no tener otra nota.");
        }

        $nota = DB::transaction(function () use ($referencia, $motivoCodigo, $motivoDescripcion) {
            $serie = $this->correlativos->seriePara('07', $referencia->tipo_doc);

            $nota = ComprobanteElectronico::create([
                'pclap_venta_id' => $referencia->pclap_venta_id,
                'tipo_doc' => '07',
                'serie' => $serie,
                'correlativo' => $this->correlativos->siguiente($serie),
                'fecha_emision' => now(),
                'cliente_tipo_doc' => $referencia->cliente_tipo_doc,
                'cliente_num_doc' => $referencia->cliente_num_doc,
                'cliente_razon_social' => $referencia->cliente_razon_social,
                'moneda' => $referencia->moneda,
                'op_gravadas' => $referencia->op_gravadas,
                'igv' => $referencia->igv,
                'total' => $referencia->total,
                'estado' => 'PENDIENTE',
                'comprobante_referencia_id' => $referencia->id,
                'motivo_codigo' => $motivoCodigo,
                'motivo_descripcion' => $motivoDescripcion,
            ]);
            $nota->detalles()->createMany($referencia->detalles->map->only(self::CAMPOS_DETALLE)->all());

            return $nota;
        });

        return $this->emisor->emitir($nota);
    }

    /** Vuelve a enviar, con el mismo número, un comprobante que no llegó a SUNAT. */
    public function reenviar(ComprobanteElectronico $comprobante): ComprobanteElectronico
    {
        if (! $comprobante->puedeReenviarse()) {
            throw new DomainException("El comprobante {$comprobante->numero()} ya tiene respuesta de SUNAT; no se reenvía.");
        }

        return $this->emisor->emitir($comprobante);
    }

    private function tieneComprobanteVigente(int $ventaId): bool
    {
        return ComprobanteElectronico::where('pclap_venta_id', $ventaId)
            ->whereIn('tipo_doc', ['01', '03'])
            ->where('estado', '!=', 'RECHAZADO')
            ->exists();
    }
}
