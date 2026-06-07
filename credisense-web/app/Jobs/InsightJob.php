<?php

namespace App\Jobs;

use App\Models\LoanApplication;
use App\Services\InsightService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class InsightJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 90;

    public function __construct(public LoanApplication $application) {}

    public function handle(InsightService $insight): void
    {
        try {
            $insight->generate($this->application);
        } catch (\Throwable $e) {
            Log::error('[InsightJob] Failed for app#' . $this->application->id . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
