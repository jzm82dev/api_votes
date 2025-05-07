<?php

namespace App\Models\Tournament;

use Carbon\Carbon;
use App\Models\Couple\Couple;
use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tournament\TournamentMatchDateCourt;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentMatch extends Model
{
    use HasFactory;
    //use SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'category_id',
        'main_draw',
        'back_draw',
        'league_number',
        'is_second_leg',
        'local_couple_id',
        'visiting_couple_id',
        'round',
        'order',
        'result_set_1',
        'result_set_2',
        'result_set_3',
        'match_finished'
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

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function local_couple(){
        return $this->belongsTo(Couple::class, 'local_couple_id');
    }

    public function visiting_couple(){
        return $this->belongsTo(Couple::class, 'visiting_couple_id');
    }

    public function match_date(){
        return $this->hasOne(TournamentMatchDateCourt::class);
    } 
}
