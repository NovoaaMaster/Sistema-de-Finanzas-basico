<?php

use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotaCreditoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\VentaPorFacturarController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', PanelController::class)->name('panel');

    Route::get('/ventas-por-facturar', [VentaPorFacturarController::class, 'index'])->name('ventas.index');
    Route::get('/ventas-por-facturar/{venta}/borrador', [VentaPorFacturarController::class, 'borrador'])->name('ventas.borrador');
    Route::post('/ventas-por-facturar/{venta}/emitir', [VentaPorFacturarController::class, 'emitir'])->name('ventas.emitir');

    Route::get('/comprobantes', [ComprobanteController::class, 'index'])->name('comprobantes.index');
    Route::get('/comprobantes/{comprobante}/pdf', [ComprobanteController::class, 'pdf'])->name('comprobantes.pdf');
    Route::get('/comprobantes/{comprobante}/xml', [ComprobanteController::class, 'xml'])->name('comprobantes.xml');
    Route::get('/comprobantes/{comprobante}/cdr', [ComprobanteController::class, 'cdr'])->name('comprobantes.cdr');
    Route::post('/comprobantes/{comprobante}/reenviar', [ComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');

    Route::get('/comprobantes/{comprobante}/nota-credito', [NotaCreditoController::class, 'create'])->name('notas.create');
    Route::post('/comprobantes/{comprobante}/nota-credito', [NotaCreditoController::class, 'store'])->name('notas.store');

    Route::get('/configuracion', ConfiguracionController::class)->name('configuracion');
});
