<?php

namespace App\Models\Tournament;

use App\Models\Court\Court;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentMatchDateCourt extends Model
{
    use HasFactory;
    //use SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'tournament_match_id',
        'court_id',
        'date',
        'match_finished'
    ];

    protected $table = "tournament_matches_date_court";

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

    public function court(){
        return $this->belongsTo(Court::class);
    }


}
