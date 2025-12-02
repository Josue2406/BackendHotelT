<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Buscar todas las asignaciones que empiecen con "Walk-in WI-"
$asignaciones = DB::table('asignacion_habitacions')
    ->where('nombre', 'LIKE', 'Walk-in WI-%')
    ->get();

echo "Encontradas " . count($asignaciones) . " asignaciones para actualizar\n\n";

foreach ($asignaciones as $asignacion) {
    $nombreAntiguo = $asignacion->nombre;
    $nombreNuevo = str_replace('Walk-in ', '', $nombreAntiguo);
    
    DB::table('asignacion_habitacions')
        ->where('id_asignacion', $asignacion->id_asignacion)
        ->update(['nombre' => $nombreNuevo]);
    
    echo "✅ ID {$asignacion->id_asignacion}: '{$nombreAntiguo}' -> '{$nombreNuevo}'\n";
}

echo "\n✅ Proceso completado\n";
