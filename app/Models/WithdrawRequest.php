<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawRequest extends Model
{


    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'amount',
        'status',
        'reason',
        'transaction_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
