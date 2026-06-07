<?php

namespace App\Jobs;

use App\Models\LoanApplication;
use App\Models\AuditLog;
use App\Services\ScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ScoringJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public LoanApplication $application) {}

    public function handle(ScoringService $scoring): void
    {
        $app = $this->application;

        try {
            $result = $scoring->scoreAndDecide($app);

            AuditLog::create([
                'user_id'             => $app->user_id,
                'loan_application_id' => $app->id,
                'action'              => 'scoring.completed',
                'actor_role'          => 'system',
                'payload'             => [
                    'decision' => $result['decision']['decision'],
                    'score'    => $result['score']['risk_score'],
                    'status'   => $result['status'],
                ],
            ]);

            // If approved, generate LLM insight
            if ($result['status'] === 'approved') {
                InsightJob::dispatch($app);
            }
        } catch (\Throwable $e) {
            Log::error('[ScoringJob] Failed for app#' . $app->id . ': ' . $e->getMessage());
            $app->update(['status' => 'pending', 'notes' => 'Scoring error: ' . $e->getMessage()]);
            throw $e;
        }
    }
}
