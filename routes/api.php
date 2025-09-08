
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TipoHabitacionController;
use App\Http\Controllers\Api\HabitacionController;
use App\Http\Controllers\Api\house_keeping\LimpiezaController;
use App\Http\Controllers\Api\house_keeping\MantenimientoController;
Route::apiResource('tipos-habitacion', TipoHabitacionController::class);
Route::apiResource('habitaciones', HabitacionController::class);
Route::apiResource('limpiezas', LimpiezaController::class);
Route::apiResource('mantenimientos', MantenimientoController::class);
