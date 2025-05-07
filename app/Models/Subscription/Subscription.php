<?php

namespace App\Models\Subscription;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'active_until',
        'club_id',
        'user_id',
        'plan_id',
        'renewal',
        'cancel_renewal_at'
    ];

    protected $dates = [
        'active_until'
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

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function plan(){
        return $this->belongsTo(Plan::class);
    }

    public function isActive(){
        $nowDate = Carbon::now();
        $dt = Carbon::parse($this->active_until);
        return $dt->gt($nowDate);
    }


    public function cancel(){
        date_default_timezone_set('Europe/Madrid');
        $this->renewal = '0';
        $this->cancel_renewal_at = Carbon::now();
        $this->save();
    }

    
}
