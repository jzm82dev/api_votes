<?php

namespace App\Models\Tournament;

use Carbon\Carbon;
use App\Models\Court\Schedule;
use App\Models\Tournament\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentScheduleDayHour extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'date',
        'opening_time',
        'closing_time'
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
}
