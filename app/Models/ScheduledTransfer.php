<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledTransfer extends Model
{
    protected $fillable = [
        'sender_account_id',
        'receiver_account_id',
        'amount',
        'description',
        'scheduled_date',
        'status',
        'failure_reason',
        'transaction_id',
        'executed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'executed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // Le compte qui envoie l'argent
    public function senderAccount()
    {
        return $this->belongsTo(Account::class, 'sender_account_id');
    }

    // Le compte qui reçoit l'argent
    public function receiverAccount()
    {
        return $this->belongsTo(Account::class, 'receiver_account_id');
    }

    // La transaction créée une fois le virement exécuté
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
