<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\LimpiezaController;
// use App\Http\Controllers\Api\MantenimientoController;
// use App\Http\Controllers\Api\Catalogos\EstadoHabitacionController;

Route::view('/', 'welcome');
Route::view('{any}', 'welcome')->where('any', '^(?!api|storage).*$');
// Route::resource('limpiezas', LimpiezaController::class);
// Route::resource('mantenimientos', MantenimientoController::class);
// Route::apiResource('estados-habitacion', EstadoHabitacionController::class);