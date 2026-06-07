<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScoringJob;
use App\Models\AuditLog;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrichController extends Controller
{
    public function edit(LoanApplication $application)
    {
        abort_if($application->status !== 'pending', 403, 'Aplikasi sudah diproses.');

        return view('admin.applications.enrich', compact('application'));
    }

    public function update(Request $request, LoanApplication $application)
    {
        abort_if($application->status !== 'pending', 403, 'Aplikasi sudah diproses.');

        $validated = $request->validate([
            'cb_person_default_on_file'  => 'required|in:Y,N',
            'cb_person_cred_hist_length' => 'required|integer|min:0|max:50',
            'notes'                      => 'nullable|string|max:500',
        ]);

        $application->update([
            'cb_person_default_on_file'  => $validated['cb_person_default_on_file'],
            'cb_person_cred_hist_length' => $validated['cb_person_cred_hist_length'],
            'loan_percent_income'        => $application->person_income > 0
                ? round($application->loan_amnt / $application->person_income, 4)
                : null,
            'notes'  => $validated['notes'] ?? null,
            'status' => 'enriched',
        ]);

        AuditLog::create([
            'user_id'             => Auth::id(),
            'loan_application_id' => $application->id,
            'action'              => 'admin.enriched',
            'actor_role'          => 'admin',
            'ip_address'          => $request->ip(),
            'payload'             => [
                'cb_default'   => $validated['cb_person_default_on_file'],
                'cred_hist_yr' => $validated['cb_person_cred_hist_length'],
            ],
        ]);

        ScoringJob::dispatch($application);

        $application->update(['status' => 'scored']);

        AuditLog::create([
            'user_id'             => Auth::id(),
            'loan_application_id' => $application->id,
            'action'              => 'scoring.dispatched',
            'actor_role'          => 'admin',
            'payload'             => [],
        ]);

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Data SLIK berhasil disimpan. Proses scoring AI sedang berjalan.');
    }
}
