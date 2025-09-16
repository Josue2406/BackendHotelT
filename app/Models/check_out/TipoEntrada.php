<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\check_out;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoEntrada
 * 
 * @property int $id_tipo_entrada_folio
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|NuevaEntradaFolio[] $nueva_entrada_folios_where_id_tipo_entrada
 *
 * @package App\Models
 */
class TipoEntrada extends Model
{
	protected $table = 'tipo_entrada';
	protected $primaryKey = 'id_tipo_entrada_folio';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function nueva_entrada_folios_where_id_tipo_entrada()
	{
		return $this->hasMany(NuevaEntradaFolio::class, 'id_tipo_entrada');
	}
}
