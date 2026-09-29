<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacturaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [FacturaController::class, 'index'])->name('facturas.index');
    Route::post('/facturas', [FacturaController::class, 'store'])->name('facturas.store');
    Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'download'])->name('facturas.download');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
