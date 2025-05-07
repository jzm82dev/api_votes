<?php

namespace App\Models\Court;

use App\Models\Club\Club;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Court extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'sport_type',
        'name',
        'description',
        'amount_without_light',
        'amount_with_light',
        'amount_member_without_light',
        'amount_member_with_light',
        'avatar'   
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

    public function day(){
        return $this->hasMany(CourtDay::class);
    }

}


