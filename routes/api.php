
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TipoHabitacionController;
use App\Http\Controllers\Api\HabitacionController;

Route::apiResource('tipos-habitacion', TipoHabitacionController::class);
Route::apiResource('habitaciones', HabitacionController::class);
