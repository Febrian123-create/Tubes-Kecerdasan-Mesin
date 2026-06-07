<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    protected $fillable = [
        'user_id',
        'person_age', 'person_income', 'person_home_ownership',
        'person_emp_length', 'loan_intent', 'loan_product', 'loan_amnt', 'loan_int_rate', 'loan_tenor_months',
        'cb_person_default_on_file', 'cb_person_cred_hist_length',
        'loan_grade', 'loan_percent_income',
        'status', 'notes',
    ];

    protected $casts = [
        'person_emp_length'      => 'float',
        'loan_percent_income'    => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scoringResult()
    {
        return $this->hasOne(ScoringResult::class);
    }

    public function loanDecision()
    {
        return $this->hasOne(LoanDecision::class);
    }

    public function llmInsight()
    {
        return $this->hasOne(LlmInsight::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
