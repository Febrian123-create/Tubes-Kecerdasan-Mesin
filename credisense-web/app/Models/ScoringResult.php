<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringResult extends Model
{
    protected $fillable = [
        'loan_application_id',
        'risk_score', 'risk_category', 'model_used', 'feature_importances',
    ];

    protected $casts = [
        'risk_score'           => 'float',
        'feature_importances'  => 'array',
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
