<?php
/**
 * Created by Reliese Model.
 */

namespace App\Models\check_out;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
 
/**
 * Class EstadoFolio
 * 
 * @property int $id_estado_folio
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Folio[] $folios_where_id_estado_folio
 *
 * @package App\Models
 */

class EstadoFolio extends Model
{
    protected $table = 'estado_folio';
    protected $primaryKey = 'id_estado_folio';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    // 🔹 Constantes para los estados
    public const ABIERTO = 1;
    public const CERRADO = 2;

    // 🔹 Nombres asociados
    public const NOMBRES = [
        self::ABIERTO => 'ABIERTO',
        self::CERRADO => 'CERRADO',
    ];

    /**
     * Retorna si el estado es válido.
     */
    public static function esEstadoValido(int $idEstado): bool
    {
        return array_key_exists($idEstado, self::NOMBRES);
    }

    /**
     * Retorna el nombre del estado según su ID.
     */
    public static function getNombre(int $idEstado): ?string
    {
        return self::NOMBRES[$idEstado] ?? null;
    }

    /**
     * Helpers para verificación directa.
     */
    public static function esAbierto(int $idEstado): bool
    {
        return $idEstado === self::ABIERTO;
    }

    public static function esCerrado(int $idEstado): bool
    {
        return $idEstado === self::CERRADO;
    }
}
