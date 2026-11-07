<?php

/*
|--------------------------------------------------------------------------
| Facturación electrónica SUNAT
|--------------------------------------------------------------------------
|
| En modo "beta" los comprobantes van al entorno de pruebas de SUNAT (gratis,
| sin validez tributaria) con el RUC y la Clave SOL públicos de prueba.
| Para producción se necesita RUC propio, Clave SOL y certificado digital.
|
*/

return [

    'modo' => env('SUNAT_MODO', 'beta'),

    'ruc' => env('SUNAT_RUC', '20000000001'),
    'usuario_sol' => env('SUNAT_USUARIO_SOL', 'MODDATOS'),
    'clave_sol' => env('SUNAT_CLAVE_SOL', 'moddatos'),

    // Ruta dentro de storage/ al certificado en formato PEM (clave privada + certificado).
    'certificado' => env('SUNAT_CERTIFICADO', 'app/sunat/certificado.pem'),

    'emisor' => [
        'razon_social' => env('SUNAT_RAZON_SOCIAL', 'PCLAP SUPPORT'),
        'nombre_comercial' => env('SUNAT_NOMBRE_COMERCIAL', 'PCLAP SUPPORT'),
        'direccion' => env('SUNAT_DIRECCION', ''),
        'ubigeo' => env('SUNAT_UBIGEO', '150101'),
        'departamento' => env('SUNAT_DEPARTAMENTO', 'LIMA'),
        'provincia' => env('SUNAT_PROVINCIA', 'LIMA'),
        'distrito' => env('SUNAT_DISTRITO', 'LIMA'),
    ],

    'igv' => 0.18,

    // Desde este total una boleta debe identificar al comprador con su documento.
    'boleta_tope_sin_documento' => 700,

];
