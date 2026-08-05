<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'account_id',
        'sender_account_id',
        'receiver_account_id',
        'type',
        'amount',
        'solde_avant',
        'solde_apres',
        'description',
        'status',
    ];


    public function account(){
        return $this->belongsTo(Account::class);
    }
}
