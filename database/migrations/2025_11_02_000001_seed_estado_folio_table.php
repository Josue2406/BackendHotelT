<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Inserta solo si la tabla existe y está vacía
        if (DB::table('estado_folio')->count() === 0) {

            $now = Carbon::now();

            DB::table('estado_folio')->insert([
                [
                    'nombre'       => 'ABIERTO',
                    'descripcion'  => 'Folio activo con operaciones pendientes o pagos en curso.',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'nombre'       => 'CERRADO',
                    'descripcion'  => 'Folio finalizado. No admite nuevas operaciones ni pagos.',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('estado_folio')
            ->whereIn('nombre', ['ABIERTO', 'CERRADO'])
            ->delete();
    }
};
