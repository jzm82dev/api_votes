<?php

namespace App\Models\Tournament;

use Carbon\Carbon;
use App\Models\Couple\Couple;
use App\Models\Category\Category;
use App\Models\Tournament\Tournament;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentCouplesClasification extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'category_id',
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

    public function tournament(){
        return $this->belongsTo(Tournament::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
