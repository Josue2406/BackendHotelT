<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\check_out;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Factura
 * 
 * @property int $id_factura
 * @property int $id_folio
 * @property string $concepto
 * @property float $monto
 * @property Carbon $fechaFactura
 * @property Carbon $fechaConsumo
 * @property int $cantidad
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class Factura extends Model
{
	protected $table = 'factura';
	protected $primaryKey = 'id_factura';

	protected $casts = [
		'id_folio' => 'int',
		'monto' => 'float',
		'fechaFactura' => 'datetime',
		'fechaConsumo' => 'datetime',
		'cantidad' => 'int'
	];

	protected $fillable = [
		'id_folio',
		'concepto',
		'monto',
		'fechaFactura',
		'fechaConsumo',
		'cantidad'
	];

	public function id_folio()
	{
		return $this->belongsTo(Folio::class, 'id_folio');
	}
}
