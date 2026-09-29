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
    Route::middleware('can:manage-users')->group(function () {
        Route::get('/usuarios', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/correo', [\App\Http\Controllers\UserController::class, 'compose'])->name('users.mail');
        Route::post('/usuarios/{user}/correo', [\App\Http\Controllers\UserController::class, 'send'])->middleware('throttle:10,1')->name('users.mail.send');
    });
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
