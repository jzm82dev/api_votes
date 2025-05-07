<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Couple\CouplePlayer;
use App\Models\Doctor\Jorge;
use App\Models\Member\ClubUser;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Doctor\Specialities;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Reservation\Reservation;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Doctor\DoctorScheduleDay;
use Illuminate\Notifications\Notifiable;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\Subsjcription;
use App\Models\Reservation\ReservationLesson;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Reservation\ReservationLessonDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    // The User model requires this trait
    use HasRoles;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        //New fields
        'surname',
        'mobile',
        'confirm_password',
        'birthday',
        'gender',
        'email_verified_at',
        'education',
        'designation',
        'address',
        'avatar',
        'specialitie_id',
        'club_id',
        'sport_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
       // 'password' => 'hashed',
    ];

     /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
 
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function specialitie(){
        return $this->belongsTo(Specialities::class);
    }

    public function schedule_days(){
        return $this->hasMany(DoctorScheduleDay::class);
    }

    public function reservations(){
        return $this->hasMany(Reservation::class);
    }

    public function reservation_lesson(){
        return $this->hasOne(ReservationLesson::class);
    }

    public function lessons(){
        return $this->hasMany(ReservationLessonDay::class);
    }

    public function club_user(){
        return $this->hasOne(ClubUser::class);
    }

    public function subscription(){
        return $this->hasOne(Subscription::class);
    }

    public function couple_player(){
        return $this->hasMany(CouplePlayer::class);
    }

    public function hasActiveSubscription(){
        return optional($this->subscription)->isActive() ?? false;
    }

}
