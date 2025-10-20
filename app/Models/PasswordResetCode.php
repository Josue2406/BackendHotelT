<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetCode extends Model
{
    protected $fillable = ['email', 'code', 'expires_at'];

    public function isExpired()
    {
        return Carbon::parse($this->expires_at)->isPast();
    }
}
