<?php
// Script temporal para actualizar códigos de walk-in en asignaciones

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener todas las asignaciones de walk-in (frontdesk sin reserva)
$asignaciones = DB::table('asignacion_habitaciones')
    ->where('origen', 'frontdesk')
    ->whereNull('id_reserva')
    ->get();

echo "Encontradas " . $asignaciones->count() . " asignaciones de walk-in\n\n";

foreach ($asignaciones as $asignacion) {
    echo "ID: {$asignacion->id_asignacion} - Nombre actual: '{$asignacion->nombre}'\n";
    
    // Buscar el código WI- en el nombre
    if (preg_match('/(WI-\d{8}-[A-Z0-9]{4})/', $asignacion->nombre, $matches)) {
        $nuevoNombre = $matches[1];
        
        // Solo actualizar si es diferente
        if ($asignacion->nombre !== $nuevoNombre) {
            DB::table('asignacion_habitaciones')
                ->where('id_asignacion', $asignacion->id_asignacion)
                ->update(['nombre' => $nuevoNombre]);
            
            echo "  ✅ Actualizado a: '{$nuevoNombre}'\n";
        } else {
            echo "  ℹ️  Ya tiene el formato correcto\n";
        }
    } else {
        echo "  ⚠️  No se encontró código WI- válido\n";
    }
    echo "\n";
}

echo "✅ Proceso completado\n";
