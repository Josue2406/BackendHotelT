<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Moneda
 * 
 * @property int $id_moneda
 * @property string $codigo
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|MetodoPago[] $metodo_pagos_where_id_moneda
 *
 * @package App\Models
 */
class Moneda extends Model
{
	protected $table = 'moneda';
	protected $primaryKey = 'id_moneda';

	protected $fillable = [
		'codigo',
		'nombre'
	];

	public function metodo_pagos_where_id_moneda()
	{
		return $this->hasMany(MetodoPago::class, 'id_moneda');
	}
}
