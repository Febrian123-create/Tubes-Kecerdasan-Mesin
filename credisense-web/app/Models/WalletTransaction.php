<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id', 'loan_application_id', 'type', 'amount', 'balance_after', 'description',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
