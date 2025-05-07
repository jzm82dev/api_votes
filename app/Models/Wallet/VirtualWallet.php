<?php

namespace App\Models\Wallet;

use App\Models\Club\Club;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualWallet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'name',
        'surname',
        'mobile',
        'amount'
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

    public function subtractsSpent($amount){
        $this->amount = $this->amount - $amount;
        $this->save();
    }

    public function chargeExpense($amount){
        $this->amount = $this->amount + $amount;
        $this->save();
    }

    public function virtual_wallet_spent(){
        return $this->hasMany(VirtualWalletSpent::class)->orderBy('id','desc');
    }

}
