<?php

namespace App\Models\Wallet;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualWalletSpent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'virtual_wallet_id',
        'info',
        'amount',
        'is_recharge'
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

    public function virtual_wallet(){
        return $this->belongsTo(VirtualWallet::class);
    }

}
