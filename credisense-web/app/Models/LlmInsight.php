<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmInsight extends Model
{
    protected $fillable = [
        'loan_application_id',
        'summary', 'approval_reasons', 'payment_plan',
        'risk_notes', 'recommendations', 'model_used', 'tokens_used',
    ];

    protected $casts = [
        'payment_plan' => 'array',
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
