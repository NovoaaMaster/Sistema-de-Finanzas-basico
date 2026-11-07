# Módulo de Finanzas del ERP PCLAP — Fase 1: facturación electrónica SUNAT

## Contexto

Carlos quiere un portafolio que llame la atención en ferias de trabajo (empresas y Estado). Ya tiene **PCLAP SUPPORT** en línea, que cubre el área comercial y logística: POS, notas de venta, inventario serializado, compras, garantías, caja chica y tienda web. Para que el conjunto se presente como un **ERP completo** faltan las dos áreas que lo definen: **Finanzas** y **Recursos Humanos**.

Decisiones de Carlos:
- Los dos módulos nuevos son **Finanzas** y **RR.HH.**
- Cada uno vive en su propia carpeta, que él ya creó y están vacías: `C:\xampp\htdocs\Portafolio\Portfolio\moduloFinanzas` y `...\moduloRrhh`.
- **PCLAP no se toca**: ni la versión en línea, ni la copia de prueba local (`C:\xampp\htdocs\Laravel\PCLAP SUPPORT`), ni su código.
- Se empieza por **Finanzas**.
- Finanzas lee las ventas y compras de la **base de prueba de PCLAP (`dbsistemapclap`) en modo SOLO LECTURA**.
- Para la demo, la facturación usa el **entorno beta de SUNAT**, que es gratis.

Este plan cubre la **Fase 1 de Finanzas**: dejar el proyecto montado y emitir boletas, facturas y notas de crédito electrónicas a partir de las ventas reales de PCLAP. Las fases 2 y 3, RR.HH. y la actualización del portafolio tendrán cada una su propio plan.

## Decisiones mías (Carlos puede cambiarlas)

- **Herramientas iguales a PCLAP** para que los tres se vean como un solo ERP: Laravel 12, PHP 8.2 (XAMPP), MySQL, Bootstrap 5.3 + jQuery + AJAX, Font Awesome, SweetAlert2 y la plantilla **SB Admin**. Los archivos `public/css/styles.css` y `public/js/scripts.js` de PCLAP se **copian**, no se mueven.
- **Greenter** (`greenter/lite`) para el XML UBL 2.1, la firma y el envío a SUNAT. **dompdf** para el PDF del comprobante, igual que PCLAP. **chillerlan/php-qrcode** para el QR obligatorio.
- **Base propia `dbfinanzas`**, más una segunda conexión `pclap` con un usuario MySQL **`pclap_lector` que solo tiene permiso SELECT** sobre `dbsistemapclap`. Aunque el código fallara, MySQL impide escribir en PCLAP.
- Servidor en el puerto **8001** (el portafolio usa el 8000).
- Fase 1 con **un solo usuario administrador**, creado por un seeder. Los roles (spatie) llegan en una fase posterior.
- Las boletas se envían de forma individual. Si la beta de SUNAT exige "resumen diario" para boletas, se usa el que trae Greenter.

## Qué hace la Fase 1

1. **Inicio de sesión** con la pantalla de login de SB Admin.
2. **Panel** con tres números: ventas de PCLAP sin comprobante electrónico, comprobantes aceptados hoy y comprobantes rechazados o con error.
3. **Ventas por facturar**: lista las ventas de PCLAP con `estado_pago = 'PAGADO'` que aún no tienen comprobante, con filtro por fechas.
   - Sugiere **Factura** si el cliente tiene RUC (11 dígitos) y **Boleta** en los demás casos.
   - Botón para emitir. Abre una vista previa con cliente, líneas (producto y número de serie), valor de venta, IGV 18 % y total.
   - Al confirmar, el comprobante se envía a SUNAT por AJAX y una ventana de SweetAlert muestra la respuesta del CDR.
4. **Comprobantes emitidos**: lista con el estado (ACEPTADO, RECHAZADO u OBSERVADO; ERROR si falla el envío) y el código y mensaje de SUNAT.
   - Se puede descargar el **PDF** (representación impresa con QR y monto en letras), el **XML** firmado y el **CDR**.
   - Botón para reenviar los que quedaron en error.
5. **Nota de crédito** sobre un comprobante aceptado, con motivo 01 (anulación) o 07 (devolución). Aparece como sugerencia cuando la venta figura `DEVUELTO` en PCLAP.
6. **Configuración**, solo para consulta: datos del emisor, modo SUNAT (beta o producción) y series con su correlativo actual.

**Fuera de la Fase 1:** cuentas por cobrar y por pagar (Fase 2); bancos, flujo de caja, estado de resultados y registro de ventas y compras en Excel (Fase 3); roles; envío por correo; producción real con SUNAT; subir el sistema a internet.

## Datos

**Lectura desde PCLAP** (conexión `pclap`, sin escritura):
- `ventas`: subtotal, impuesto, total, estado_pago, numero_comprobante, cliente_id, cliente_nuevo y fecha_hora.
- `producto_venta`: cantidad y precio_venta, unida a `productos` para el nombre y el código.
- `series`: número de serie por `venta_id`, para la descripción de cada línea.
- `clientes` → `personas` → `documentos`: razón social, número y tipo de documento.
- `comprobantes`: nombre del tipo usado en PCLAP.
- `empresa`: nombre, RUC, dirección y porcentaje de impuesto.

**Tablas nuevas en `dbfinanzas`:**
- `series_electronicas`: tipo (01, 03, 07), serie (F001, B001, FC01, BC01) y correlativo.
- `comprobantes_electronicos`:
  - Referencia a PCLAP: `pclap_venta_id`.
  - Identificación: tipo, serie, correlativo y fecha.
  - Cliente: tipo y número de documento, y razón social.
  - Montos: moneda, op_gravadas, igv y total.
  - Respuesta de SUNAT: estado, sunat_codigo, sunat_mensaje y hash.
  - Archivos: xml_path y cdr_path.
  - Solo para notas de crédito: comprobante_referencia_id y motivo.
- `comprobante_detalles`: descripción, cantidad, valor_unitario sin IGV, precio_unitario con IGV, igv y total de la línea.

**Modelos de PCLAP de solo lectura:** una clase base `App\Pclap\ModeloPclap` con `$connection = 'pclap'`, cuyos `save()` y `delete()` lanzan una excepción. Así hay doble protección, junto con el usuario MySQL.

**Pendiente de verificar con datos reales en el paso 3:** si `precio_venta` de PCLAP **incluye** el IGV. Se asume que sí (precio al público), y el valor sin IGV se calcula como `precio / 1.18`. Si la suma no cuadra con `ventas.total`, la vista previa lo advierte.

## Pasos

Antes de cada cambio de sistema (php.ini, MySQL) se le avisa a Carlos y se espera su OK. Se avanza paso a paso, mostrando cada parte.

1. **Preparar el entorno** (requiere OK):
   - Respaldar `C:\xampp\php\php.ini` como `php.ini.bak-before-soap`.
   - Activar `extension=soap`, que Greenter necesita, y `extension=gd`, para el QR en PNG dentro del PDF.
   - Encender MySQL en XAMPP.
2. **Crear el proyecto**: `composer create-project laravel/laravel:^12.0 .` dentro de `moduloFinanzas`. Configurar `.env` con APP_NAME "PCLAP Finanzas", MySQL y `dbfinanzas`, y ejecutar `key:generate`.
3. **Conexión de solo lectura** (requiere OK):
   - Crear la base `dbfinanzas` y el usuario `pclap_lector` con `GRANT SELECT ON dbsistemapclap.*`.
   - Agregar la conexión `pclap` en `config/database.php`, con variables `PCLAP_DB_*` en `.env`.
   - Revisar los datos reales: ventas, tipos de comprobante, documentos, y si los precios incluyen IGV.
4. **Instalar paquetes**: `greenter/lite`, `barryvdh/laravel-dompdf`, `chillerlan/php-qrcode` y un convertidor de número a letras (por ejemplo `luecano/numero-a-letras`).
5. **Configuración SUNAT** en `config/sunat.php`, leída desde `.env`:
   - Modo beta.
   - RUC `20000000001`, usuario `MODDATOS`, clave `moddatos`.
   - Certificado de prueba autofirmado generado con OpenSSL y guardado en `storage/app/sunat/` (no se versiona).
   - Ubigeo y dirección del emisor; para la beta, ubigeo 150101.
6. **Migraciones, modelos y seeders**: tablas de Finanzas, series iniciales y usuario administrador.
7. **Lógica con pruebas primero** (TDD):
   - `CalculadoraIgv`: valor sin IGV, IGV y redondeos.
   - `MapeadorVentaPclap`: convierte una venta de PCLAP en el comprobante de Greenter (tipo sugerido, cliente, líneas con serie, totales y leyenda en letras).
   - `EmisorSunat`: envía, guarda XML y CDR, y actualiza el estado. En las pruebas se simula Greenter; hay una prueba real contra la beta en el grupo `sunat`, excluida por defecto.
   - `CorrelativoService`: numeración sin saltos ni duplicados (con transacción y `lockForUpdate`).
8. **Pantallas** con SB Admin y la barra lateral de "Finanzas": login, panel, ventas por facturar con su vista previa, comprobantes emitidos con sus descargas, nota de crédito y configuración.
9. **PDF del comprobante** con dompdf: datos del emisor, cliente, líneas, totales, monto en letras, QR (con el formato que pide SUNAT) y hash. El diseño parte de una **copia** de la boleta en PDF de PCLAP (`pclapsistema/resources/views/pdf/`), para que se vea igual que el comprobante de la tienda.
10. **Documentación**: guardar este diseño en `moduloFinanzas/docs/diseno-finanzas.md` y crear un `CLAUDE.md` del módulo con los comandos y la regla "PCLAP es solo lectura".

## Archivos clave (en `moduloFinanzas/`)

- `config/database.php` (conexión `pclap`) y `config/sunat.php`
- `app/Pclap/ModeloPclap.php`, más `VentaPclap`, `ClientePclap` y otros modelos de solo lectura
- `app/Services/Facturacion/` con `CalculadoraIgv`, `MapeadorVentaPclap`, `EmisorSunat` y `CorrelativoService`
- `app/Models/ComprobanteElectronico.php`, `ComprobanteDetalle.php` y `SerieElectronica.php`
- `app/Http/Controllers/` con `PanelController`, `VentaPorFacturarController`, `ComprobanteController` y `NotaCreditoController`
- `resources/views/layouts/app.blade.php`, una copia adaptada del layout SB Admin de PCLAP (`pclapsistema/resources/views/layouts/`)
- `resources/views/pdf/comprobante.blade.php`
- `tests/Unit/` y `tests/Feature/`. En las pruebas, ambas conexiones usan SQLite en memoria con un esquema mínimo de PCLAP creado por la propia prueba.

## Verificación

1. `php artisan test` pasa en verde (sin el grupo `sunat`).
2. `php artisan test --group=sunat` envía una boleta a la beta y SUNAT la acepta (código 0).
3. Prueba manual con `php artisan serve --port=8001`:
   - Iniciar sesión, elegir una venta real de la base de prueba de PCLAP y emitir la boleta. Debe salir **ACEPTADO**.
   - Descargar el PDF y revisar el QR, el monto en letras y los totales; descargar también el XML y el CDR.
   - Emitir una factura a un cliente con RUC y una nota de crédito sobre ella.
4. **PCLAP intacto:**
   - Un `UPDATE` con `pclap_lector` debe ser rechazado por MySQL.
   - `find "C:/xampp/htdocs/Laravel/PCLAP SUPPORT" -newer <marca de inicio>` no debe listar ningún archivo.

## Decisiones tomadas durante la construcción (6 de noviembre de 2026)

- **IGV incluido en el precio de PCLAP.** La empresa en PCLAP tiene `porcentaje_impuesto = 0` y en cada venta se cumple total = subtotal = suma de las líneas. Por eso `precio_venta` se toma como precio final con IGV, y el valor sin IGV se calcula en céntimos: `round(total / 1.18)`.
- **El cliente se puede corregir en la vista previa.** La venta de prueba registrada como factura en PCLAP es a "Público General", sin RUC. El tipo de documento, el número y la razón social se editan antes de emitir y se guardan solo en Finanzas.
- **Reglas que se validan:**
  - La factura exige un RUC de 11 dígitos.
  - El DNI debe tener 8 dígitos.
  - Una boleta de más de S/ 700 debe identificar al cliente.
  - Un cliente sin documento se envía como tipo "-" (catálogo 06, "varios").
- **Los datos del emisor vienen de `config/sunat.php` y del `.env`, no de PCLAP.** El RUC debe coincidir con el usuario SOL, y el RUC de la base de prueba de PCLAP no es válido (tiene 10 dígitos).
- **La fecha de emisión es el momento de emitir.** No es la fecha de la venta, porque SUNAT limita la antigüedad de la fecha de un comprobante.
- **Separación en dos pasos:** `MapeadorVentaPclap` convierte la venta en un registro local, y `ConstructorGreenter` convierte ese registro en el documento de Greenter. Así el reenvío y las notas de crédito no dependen de PCLAP.
- **Motivos de nota de crédito:** 01 (anulación de la operación), 02 (anulación por error en el RUC) y 06 (devolución total). La nota siempre cubre el comprobante completo, por eso se usa 06 en lugar de 07 (devolución por ítem).
- **Envíos casi seguidos.** La beta de SUNAT rechaza con "Unauthorized" los envíos que llegan casi juntos; se comprobó que con 5 segundos de pausa los acepta. El comprobante queda en ERROR y se reenvía con el mismo número.

## Resultado de la verificación

- `php artisan test`: 67 pruebas en verde. `php artisan test --group=sunat`: la beta real aceptó una boleta, una factura y una nota de crédito.
- Recorrido manual con datos reales de PCLAP (base `dbsistemapclap`), todo con respuesta **ACEPTADO** de SUNAT:
  - Boleta B001-1 (venta 28).
  - Factura F001-1 (venta 3, con RUC).
  - Nota de crédito FC01-1.
- Las descargas de PDF, XML y CDR funcionan. La factura sin RUC se rechaza con el mensaje "La factura exige el RUC del cliente."
- PCLAP intacto:
  - El usuario `pclap_lector` no puede hacer UPDATE ni DELETE (MySQL los rechaza con el error 1142).
  - No se modificó ningún archivo de PCLAP.
