<?php

namespace App\Models\Club;

use Carbon\Carbon;
use App\Models\Court\Court;
use App\Models\Player\Player;
use App\Models\Club\ClubScheduleDay;
use App\Models\Member\ClubUser;
use App\Models\Subscription\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Club extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cif',
        'club_manager',
        'email',
        'mobile',
        'users_can_book',
        'address',
        'avatar',   
        'hash',
        'club_verified_at'
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

    public function additional_information(){
        return $this->hasOne(ClubAdditionalInformation::class);
    }

    public function players(){
        return $this->hasMany(Player::class);
    }

    public function courts(){
        return $this->hasMany(Court::class);
    }

    public function clubScheduleDay(){
        return $this->hasMany(ClubScheduleDay::class);
    }

    public function services(){
        return $this->hasOne(ClubService::class);
    }
    
    public function social_links(){
        return $this->hasOne(ClubSocialLink::class);
    }

    public function users(){
        return $this->hasMany(ClubUser::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }

    public function clubScheduleSpecialDay(){
        return $this->hasMany(ClubScheduleSpecialDay::class);
    }
    
}
