<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Notifications\TransactionNotification;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'reference',
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

    protected static function boot()
    {
        parent::boot();

        // Événement exécuté avant la création de la transaction
        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                // Génère une référence du type : TXN-8AX92B1C
                $transaction->reference = 'TXN-' . strtoupper(Str::random(10));
            }
        });
    }

    public function account(){
        return $this->belongsTo(Account::class);
    }

    public function senderAccount(){
        return $this->belongsTo(Account::class,'sender_account_id');
    }

    public function receiverAccount(){
        return $this->belongsTo(Account::class,'receiver_account_id');
    }


}
