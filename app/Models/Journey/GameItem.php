<?php

namespace App\Models\Journey;

use App\Models\Category\Category;
use Carbon\Carbon;
use App\Models\Player\Player;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameItem extends Model
{
    use HasFactory;
    //use SoftDeletes;
    
    protected $fillable = [
        'journey_id',
        'category_id',
        'game_id',
        'game_number',
        'local_player_1',
        'local_player_2',
        'visiting_player_1',
        'visiting_player_2',
        'result_set_1',
        'result_set_2',
        'result_set_3',
        'cron_executed_at'
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

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function journey(){
        return $this->belongsTo(Journey::class);
    }

    public function game(){
        return $this->belongsTo(Game::class);
    }

    public function local_player_1(){
        return $this->belongsTo(Player::class,'local_player_1', 'id');
    }

    public function local_player_2(){
        return $this->belongsTo(Player::class, 'local_player_2');
    }

    public function visiting_player_1(){
        return $this->belongsTo(Player::class, 'visiting_player_1');
    }

    public function visiting_player_2(){
        return $this->belongsTo(Player::class, 'visiting_player_2');
    }

}
