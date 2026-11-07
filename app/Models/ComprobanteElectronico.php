<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComprobanteElectronico extends Model
{
    use HasFactory;

    protected $table = 'comprobantes_electronicos';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'correlativo' => 'integer',
        'op_gravadas' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const TIPOS = [
        '01' => 'Factura',
        '03' => 'Boleta',
        '07' => 'Nota de crédito',
    ];

    /** Motivos del catálogo 09 de SUNAT que aplican a una nota de crédito por el total del comprobante. */
    public const MOTIVOS_NOTA_CREDITO = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '06' => 'Devolución total',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    /** Comprobante que modifica una nota de crédito. */
    public function referencia(): BelongsTo
    {
        return $this->belongsTo(self::class, 'comprobante_referencia_id');
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(self::class, 'comprobante_referencia_id');
    }

    public function numero(): string
    {
        return $this->serie.'-'.$this->correlativo;
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo_doc] ?? $this->tipo_doc;
    }

    /** Título de la representación impresa. */
    public function tituloImpreso(): string
    {
        return match ($this->tipo_doc) {
            '01' => 'FACTURA ELECTRÓNICA',
            '03' => 'BOLETA DE VENTA ELECTRÓNICA',
            '07' => 'NOTA DE CRÉDITO ELECTRÓNICA',
        };
    }

    /** Nombre que SUNAT espera para el XML: RUC-TIPO-SERIE-NUMERO. */
    public function nombreArchivo(): string
    {
        return config('sunat.ruc').'-'.$this->tipo_doc.'-'.$this->numero();
    }

    /** Texto del código QR de la representación impresa. */
    public function contenidoQr(): string
    {
        return implode('|', [
            config('sunat.ruc'),
            $this->tipo_doc,
            $this->serie,
            $this->correlativo,
            $this->igv,
            $this->total,
            $this->fecha_emision->format('Y-m-d'),
            $this->cliente_tipo_doc,
            $this->cliente_num_doc,
            $this->hash,
        ]).'|';
    }

    /** ERROR significa que el comprobante no llegó a SUNAT: se puede volver a enviar con el mismo número. */
    public function puedeReenviarse(): bool
    {
        return $this->estado === 'ERROR';
    }

    public function admiteNotaCredito(): bool
    {
        return in_array($this->tipo_doc, ['01', '03'], true)
            && in_array($this->estado, ['ACEPTADO', 'OBSERVADO'], true)
            && ! $this->notasCredito()->exists();
    }
}
