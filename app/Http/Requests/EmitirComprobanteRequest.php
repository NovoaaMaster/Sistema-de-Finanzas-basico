<?php

namespace App\Http\Requests;

use App\Services\Facturacion\MapeadorVentaPclap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Datos del comprobante que se confirman en la vista previa antes de enviarlo a SUNAT. */
class EmitirComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cliente_num_doc' => $this->input('cliente_tipo_doc') === '-' ? '-' : trim((string) $this->input('cliente_num_doc')),
            'cliente_razon_social' => trim((string) $this->input('cliente_razon_social')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_doc' => ['required', Rule::in(['01', '03'])],
            'cliente_tipo_doc' => ['required', Rule::in(['1', '6', '-'])],
            'cliente_num_doc' => ['required', 'string', 'max:15'],
            'cliente_razon_social' => ['required', 'string', 'max:200'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tipoCliente = $this->input('cliente_tipo_doc');
            $numero = $this->input('cliente_num_doc');

            if ($tipoCliente === '6' && preg_match('/^\d{11}$/', $numero) !== 1) {
                $validator->errors()->add('cliente_num_doc', 'El RUC debe tener 11 dígitos.');
            }
            if ($tipoCliente === '1' && preg_match('/^\d{8}$/', $numero) !== 1) {
                $validator->errors()->add('cliente_num_doc', 'El DNI debe tener 8 dígitos.');
            }
            if ($this->input('tipo_doc') === '01' && $tipoCliente !== '6') {
                $validator->errors()->add('cliente_tipo_doc', 'La factura exige el RUC del cliente.');
            }
            if ($this->input('tipo_doc') === '03' && $tipoCliente === '-' && $this->totalVenta() > config('sunat.boleta_tope_sin_documento')) {
                $validator->errors()->add('cliente_tipo_doc', 'Una boleta de más de S/ '.config('sunat.boleta_tope_sin_documento').' debe identificar al cliente con su DNI o RUC.');
            }
        }];
    }

    private function totalVenta(): float
    {
        return app(MapeadorVentaPclap::class)->borrador($this->route('venta'))['totales']['total'];
    }
}
