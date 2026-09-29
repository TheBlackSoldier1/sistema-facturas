<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\WorkflowController;
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
    Route::get('/historial', [WorkflowController::class, 'history'])->name('history');
    Route::get('/notificaciones', [WorkflowController::class, 'notifications'])->name('notifications');
    Route::post('/notificaciones/{aviso}/leer', [WorkflowController::class, 'read'])->name('notifications.read');
    Route::get('/facturas/{factura}', [WorkflowController::class, 'show'])->withTrashed()->name('facturas.show');
    Route::patch('/facturas/{factura}', [WorkflowController::class, 'update'])->name('facturas.update');
    Route::post('/facturas/{factura}/accion', [WorkflowController::class, 'action'])->withTrashed()->name('facturas.action');
    Route::post('/facturas/{factura}/documentos', [WorkflowController::class, 'upload'])->name('facturas.upload');
    Route::get('/facturas/{factura}/documentos/{documento}', [WorkflowController::class, 'document'])->withTrashed()->name('facturas.document');
});
