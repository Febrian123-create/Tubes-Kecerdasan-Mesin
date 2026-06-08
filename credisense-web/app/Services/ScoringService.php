<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\ScoringResult;
use App\Models\LoanDecision;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScoringService
{
    public function scoreAndDecide(LoanApplication $app): array
    {
        // loan_grade is auto-derived by Python agent from loan_int_rate — not sent manually
        $payload = [
            'person_age'                 => $app->person_age,
            'person_income'              => $app->person_income,
            'person_home_ownership'      => $app->person_home_ownership,
            'person_emp_length'          => $app->person_emp_length,
            'loan_intent'                => $app->loan_intent,
            'loan_amnt'                  => $app->loan_amnt,
            'loan_int_rate'              => $app->loan_int_rate,
            'loan_percent_income'        => $app->loan_percent_income,
            'cb_person_default_on_file'  => $app->cb_person_default_on_file,
            'cb_person_cred_hist_length' => $app->cb_person_cred_hist_length,
        ];

        // Call Agent 1 — scoring
        $scoreResp = Http::timeout(30)->post(
            config('services.ml.scoring_url') . '/score',
            $payload
        );

        if (!$scoreResp->successful()) {
            Log::error('[ScoringService] Agent1 error', ['body' => $scoreResp->body()]);
            throw new \RuntimeException('Scoring service unavailable: ' . $scoreResp->status());
        }

        $scoreData = $scoreResp->json();

        ScoringResult::updateOrCreate(
            ['loan_application_id' => $app->id],
            [
                'risk_score'          => $scoreData['risk_score'],
                'risk_category'       => $scoreData['risk_category'],
                'model_used'          => $scoreData['model_used'],
                'feature_importances' => $scoreData['feature_importances'] ?? [],
            ]
        );

        // Call Agent 2 — decision
        $decResp = Http::timeout(30)->post(
            config('services.ml.decision_url') . '/decide',
            [
                'risk_score'     => $scoreData['risk_score'],
                'risk_category'  => $scoreData['risk_category'],
                'applicant_data' => array_merge($payload, ['loan_grade' => $app->loan_grade]),
            ]
        );

        if (!$decResp->successful()) {
            Log::error('[ScoringService] Agent2 error', ['body' => $decResp->body()]);
            throw new \RuntimeException('Decision service unavailable: ' . $decResp->status());
        }

        $decData = $decResp->json();

        LoanDecision::updateOrCreate(
            ['loan_application_id' => $app->id],
            [
                'decision'         => $decData['decision'],
                'approved_amount'  => $decData['max_approved_amount'] ?? null,
                'conditions'       => $decData['conditions'] ?? null,
                'decline_reason'   => $decData['decline_reason'] ?? null,
                'rules_triggered'  => $decData['rules_triggered'] ?? [],
            ]
        );

        $finalStatus = match ($decData['decision']) {
            'APPROVED' => 'approved',
            'DECLINED' => 'rejected',
            default    => 'review',
        };

        $app->update(['status' => $finalStatus]);

        if ($finalStatus === 'approved') {
            $this->creditWallet($app, $decData['max_approved_amount'] ?? $app->loan_amnt);
        }

        return [
            'score'    => $scoreData,
            'decision' => $decData,
            'status'   => $finalStatus,
        ];
    }

    private function creditWallet(LoanApplication $app, float $amount): void
    {
        DB::transaction(function () use ($app, $amount) {
            $user = $app->user()->lockForUpdate()->first();
            $user->increment('balance', $amount);

            $product = config('loan_products.'.$app->loan_product);

            WalletTransaction::create([
                'user_id'             => $user->id,
                'loan_application_id' => $app->id,
                'type'                => 'credit',
                'amount'              => $amount,
                'balance_after'       => $user->balance,
                'description'         => 'Pencairan pinjaman #'.$app->id.' — '.($product['name'] ?? 'Kredit'),
            ]);
        });
    }
}
