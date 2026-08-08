<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'user_id',
        'account_number',
        'nickname',
    ];

    // L'utilisateur propriétaire de ce bénéficiaire
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
