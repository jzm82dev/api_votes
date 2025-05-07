<?php

namespace App\Models\Club;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfirmEmail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'user_id',
        'email',
        'token'
    ];
    
    protected $dates = [
        'created_at'
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('Europe/Madrid');
        $this->attributes['created_at'] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('Europe/Madrid');
        $this->attributes['updated_at'] = Carbon::now();
    }

    public function club(){
        return $this->belongsTo(Club::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function isActiveToken(){
        $validUntil = Carbon::now();
        $dt = Carbon::parse($this->created_at)->addHours(24);
        return $dt->gt($validUntil);
    }
}
