<?php

namespace App\Models\Couple;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoupleResult extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'couple_id',
        'total_points',
        'matches_played',
        'matchs_won',
        'matchs_lost',
        'games_won',
        'games_lost',
        'games_avg',
        'sets_won',
        'sets_lost'
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

    public function couple(){
        return $this->belongsTo(Couple::class, 'couple_id');
    }

}
