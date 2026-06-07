<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDecision extends Model
{
    protected $fillable = [
        'loan_application_id',
        'decision', 'approved_amount', 'conditions', 'decline_reason', 'rules_triggered',
    ];

    protected $casts = [
        'rules_triggered' => 'array',
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
