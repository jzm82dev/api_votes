<?php

namespace App\Models\User;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token'
    ];

    protected $dates = [
        'created_at'
    ];

    protected $primaryKey = 'email';
    protected $keyType = 'string';

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('Europe/Madrid');
        $this->attributes['created_at'] = Carbon::now();
    }

    public function isActiveToken(){
        $validUntil = Carbon::now();
        //$nowDate->addHour(24);
        $dt = Carbon::parse($this->created_at)->addHours(24);
        return $dt->gt($validUntil);
    }


    
}
