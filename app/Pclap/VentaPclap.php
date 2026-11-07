<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaPclap extends ModeloPclap
{
    protected $table = 'ventas';

    protected $casts = [
        'fecha_hora' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClientePclap::class, 'cliente_id');
    }

    /** Boleta, Factura o Nota de venta (tabla "comprobantes" de PCLAP). */
    public function tipoComprobante(): BelongsTo
    {
        return $this->belongsTo(TipoComprobantePclap::class, 'comprobante_id');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(ProductoPclap::class, 'producto_venta', 'venta_id', 'producto_id')
            ->withPivot('cantidad', 'precio_venta');
    }

    public function series(): HasMany
    {
        return $this->hasMany(SeriePclap::class, 'venta_id');
    }

    /** Ventas cobradas: excluye notas de venta pendientes y devueltas. */
    public function scopePagadas(Builder $query): void
    {
        $query->where('estado_pago', 'PAGADO');
    }
}
