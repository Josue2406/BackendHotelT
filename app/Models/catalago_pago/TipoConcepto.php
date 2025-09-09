<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoConcepto
 * 
 * @property int $id_tipo_concepto_folio
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|NuevaEntradaFolio[] $nueva_entrada_folios_where_id_tipo_concepto
 *
 * @package App\Models
 */
class TipoConcepto extends Model
{
	protected $table = 'tipo_concepto';
	protected $primaryKey = 'id_tipo_concepto_folio';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function nueva_entrada_folios_where_id_tipo_concepto()
	{
		return $this->hasMany(NuevaEntradaFolio::class, 'id_tipo_concepto');
	}
}
