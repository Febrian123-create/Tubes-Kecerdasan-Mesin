<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total'    => LoanApplication::count(),
            'pending'  => LoanApplication::whereIn('status', ['pending', 'enriched'])->count(),
            'approved' => LoanApplication::where('status', 'approved')->count(),
            'rejected' => LoanApplication::where('status', 'rejected')->count(),
            'review'   => LoanApplication::where('status', 'review')->count(),
        ];

        $recent = LoanApplication::with(['user', 'scoringResult', 'loanDecision'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}
