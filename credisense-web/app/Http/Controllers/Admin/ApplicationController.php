<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = LoanApplication::with(['user', 'scoringResult', 'loanDecision'])
            ->latest()
            ->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    public function show(LoanApplication $application)
    {
        $application->load(['user', 'scoringResult', 'loanDecision', 'llmInsight', 'auditLogs.user']);

        return view('admin.applications.show', compact('application'));
    }
}
