<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea en la conexión "pclap" (SQLite en memoria durante las pruebas) las tablas de PCLAP
 * que lee Finanzas, con las mismas columnas que la base real. Los datos se insertan con el
 * query builder porque los modelos de app/Pclap son de solo lectura.
 */
trait ConEsquemaPclap
{
    protected function crearEsquemaPclap(): void
    {
        $schema = Schema::connection('pclap');

        $schema->create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
        });
        $schema->create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('tipo')->default('NATURAL');
            $table->foreignId('documento_id');
            $table->string('numero_documento', 20);
        });
        $schema->create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id');
        });
        $schema->create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
        });
        $schema->create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('nombre');
        });
        $schema->create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id');
            $table->string('cliente_nuevo')->nullable();
            $table->foreignId('comprobante_id');
            $table->string('numero_comprobante');
            $table->string('metodo_pago')->default('EFECTIVO');
            $table->dateTime('fecha_hora');
            $table->decimal('subtotal', 8, 2);
            $table->decimal('impuesto', 8, 2)->default(0);
            $table->decimal('total', 8, 2);
            $table->string('estado_pago', 20)->default('PAGADO');
        });
        $schema->create('producto_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id');
            $table->foreignId('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_venta', 10, 2);
        });
        $schema->create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id');
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->string('numero_serie');
            $table->string('estado')->default('vendido');
        });

        DB::connection('pclap')->table('documentos')->insert([
            ['id' => 1, 'nombre' => 'DNI'],
            ['id' => 3, 'nombre' => 'RUC'],
        ]);
        DB::connection('pclap')->table('comprobantes')->insert([
            ['id' => 1, 'nombre' => 'Boleta'],
            ['id' => 2, 'nombre' => 'Factura'],
            ['id' => 3, 'nombre' => 'Nota de venta'],
        ]);
    }

    /**
     * Inserta una venta de PCLAP y devuelve su id.
     *
     * Opciones: comprobante ('Boleta'|'Factura'|'Nota de venta'), estado_pago, fecha_hora, cliente_nuevo,
     * cliente (['razon_social', 'documento' => 'DNI'|'RUC', 'numero']), total (si no, la suma de líneas)
     * y lineas ([['producto', 'codigo', 'cantidad', 'precio', 'series' => [...]]]).
     */
    protected function crearVentaPclap(array $opciones = []): int
    {
        $db = DB::connection('pclap');

        $cliente = $opciones['cliente'] ?? ['razon_social' => 'Público General', 'documento' => 'DNI', 'numero' => '00000000'];
        $personaId = $db->table('personas')->insertGetId([
            'razon_social' => $cliente['razon_social'],
            'documento_id' => $cliente['documento'] === 'RUC' ? 3 : 1,
            'numero_documento' => $cliente['numero'],
        ]);
        $clienteId = $db->table('clientes')->insertGetId(['persona_id' => $personaId]);

        $lineas = $opciones['lineas'] ?? [
            ['producto' => 'placa h510', 'codigo' => '000403037473', 'cantidad' => 1, 'precio' => 144.00, 'series' => ['515151']],
        ];
        $sumaLineas = array_sum(array_map(fn ($linea) => $linea['cantidad'] * $linea['precio'], $lineas));
        $total = $opciones['total'] ?? $sumaLineas;

        $ventaId = $db->table('ventas')->insertGetId([
            'cliente_id' => $clienteId,
            'cliente_nuevo' => $opciones['cliente_nuevo'] ?? null,
            'comprobante_id' => $db->table('comprobantes')->where('nombre', $opciones['comprobante'] ?? 'Boleta')->value('id'),
            'numero_comprobante' => '0002 - '.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'fecha_hora' => $opciones['fecha_hora'] ?? '2026-10-24 18:57:03',
            'subtotal' => $total,
            'total' => $total,
            'estado_pago' => $opciones['estado_pago'] ?? 'PAGADO',
        ]);

        foreach ($lineas as $linea) {
            $productoId = $db->table('productos')->insertGetId(['codigo' => $linea['codigo'] ?? null, 'nombre' => $linea['producto']]);
            $db->table('producto_venta')->insert([
                'venta_id' => $ventaId,
                'producto_id' => $productoId,
                'cantidad' => $linea['cantidad'],
                'precio_venta' => $linea['precio'],
            ]);
            foreach ($linea['series'] ?? [] as $serie) {
                $db->table('series')->insert(['producto_id' => $productoId, 'venta_id' => $ventaId, 'numero_serie' => $serie]);
            }
        }

        return $ventaId;
    }
}
