<?php

use App\Http\Controllers\FacturaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FacturaController::class, 'index'])->name('facturas.index');
Route::post('/facturas', [FacturaController::class, 'store'])->name('facturas.store');
Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'download'])->name('facturas.download');
