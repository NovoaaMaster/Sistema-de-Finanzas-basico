<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteDetalle extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'valor_unitario' => 'decimal:6',
        'precio_unitario' => 'decimal:2',
        'valor_venta' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(ComprobanteElectronico::class, 'comprobante_electronico_id');
    }
}
