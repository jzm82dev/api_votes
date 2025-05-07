<?php

namespace App\Models\Couple;

use Carbon\Carbon;
use App\Models\Tournament\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoupleNotPlayHourTournament extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'couple_id',
        'date',
        'start_time',
        'end_time'
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

    public function tournament(){
        return $this->belongsTo(Tournament::class);
    }

    public function couple(){
        return $this->belongsTo(Couple::class);
    }
}
