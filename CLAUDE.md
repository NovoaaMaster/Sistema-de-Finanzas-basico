# Módulo de Finanzas (ERP PCLAP) — instrucciones para Claude

Responde siempre en español. Antes de empezar cualquier tarea, pregunta (1–3 preguntas, con opciones y tu recomendación).

## Qué es

Segundo sistema del ERP de Carlos: **PCLAP SUPPORT** (ventas, compras, inventario; en línea) + **Finanzas** (este módulo) + **RR.HH.** (`../moduloRrhh`, pendiente).
Fase 1: facturación electrónica SUNAT (boletas, facturas y notas de crédito) a partir de las ventas de PCLAP. Diseño completo en `docs/diseno-finanzas.md`.

## Regla de oro: PCLAP es solo lectura

- **Nunca** modifiques archivos de `C:\xampp\htdocs\Laravel\PCLAP SUPPORT` ni la versión en línea.
- Finanzas lee la base de prueba `dbsistemapclap` con la conexión `pclap` y el usuario MySQL `pclap_lector`, que **solo tiene SELECT**.
- Los modelos de `app/Pclap/` lanzan una excepción al guardar o borrar. No lo cambies.

## Piezas

| Pieza | Dónde |
|---|---|
| Stack | Laravel 12, PHP 8.2 (XAMPP, con `soap` y `gd`), MySQL `dbfinanzas`, Bootstrap 5 + jQuery + base SB Admin |
| Identidad visual | Propia, distinta de PCLAP: `public/css/finanzas.css` (petróleo `#0F4C5C`/`#0F766E`, barra lateral blanca), IBM Plex Sans y logo "S/" (`components/logo.blade.php`). No uses los colores de PCLAP (negro y azul). |
| SUNAT | Greenter (`greenter/lite`); configuración en `config/sunat.php` y `.env` (`SUNAT_*`) |
| Lógica | `app/Services/Facturacion/` (CalculadoraIgv, MapeadorVentaPclap, CorrelativoService, ConstructorGreenter, EmisorSunat, FacturacionService, RepresentacionImpresa) |
| Certificado de prueba | `storage/app/sunat/certificado.pem` (autofirmado, no se versiona) |
| XML y CDR emitidos | `storage/app/private/comprobantes/` |

## Comandos

```bash
php artisan serve --port=8001     # http://127.0.0.1:8001 (el portafolio usa el 8000)
php artisan migrate --seed        # tablas de Finanzas, series y usuario admin (FINANZAS_ADMIN_* del .env)
php artisan test                  # pruebas sin internet (SUNAT simulado)
php artisan test --group=sunat    # envío real a la beta de SUNAT (necesita internet y el certificado)
```

## Cosas que ya aprendimos

- La beta de SUNAT responde **"Unauthorized"** si dos envíos llegan casi juntos. El comprobante queda en ERROR y se reenvía unos segundos después (botón "Reenviar").
- La beta no controla números repetidos: las series F001/B001/FC01/BC01 sirven aunque otros las usen.
- En PCLAP `precio_venta` ya incluye el IGV (la empresa tiene `porcentaje_impuesto = 0`); Finanzas separa el IGV con `CalculadoraIgv`.
- Al probar con `curl` en Windows, los acentos se envían en Latin-1 y MySQL los rechaza. Envía el texto ya codificado (`%C3%B3`) o prueba desde el navegador.
