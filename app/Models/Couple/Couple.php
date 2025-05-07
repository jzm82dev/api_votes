<?php

namespace App\Models\Couple;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\League\League;
use App\Models\Category\Category;
use App\Models\Tournament\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Couple extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'category_id',
        'league_id',
        'tournament_id',
        'name',
        'description',
        'matches_played',
        'to_back_draw'
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

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function league(){
        return $this->belongsTo(League::class);
    }

    public function tournament(){
        return $this->belongsTo(Tournament::class);
    }

    public function players(){
        return $this->hasMany(CouplePlayer::class)->where('substitute', '=', '0');
    }

    public function substitutePlayer(){
        return $this->hasMany(CouplePlayer::class)->where('substitute', '=', '1');
    }

    public function results(){
        return $this->hasMany(CoupleResult::class);
    }

    public function scheduleNotPlay(){
        return $this->hasMany(CoupleNotPlayHourTournament::class);
    }
}
