<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Modulo_Fua\FuaElectronicaController;
use App\Http\Controllers\Modulo_Administrador\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas para el Módulo Administrador
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
    });
    
    // Rutas para el Módulo FUA
    Route::prefix('fua')->name('fua.')->group(function () {
        Route::get('/', [FuaElectronicaController::class, 'index'])->name('index');
        Route::get('/create', [FuaElectronicaController::class, 'create'])->name('create');
        Route::post('/store', [FuaElectronicaController::class, 'store'])->name('store');

        Route::post('/fua/validar', [FuaElectronicaController::class, 'validar'])->name('validar');

        Route::delete('/limpiar-bd', [FuaElectronicaController::class, 'destroyAll'])->name('destroyAll');
    });
});

require __DIR__.'/auth.php';
