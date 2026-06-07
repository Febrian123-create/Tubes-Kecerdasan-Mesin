<?php

namespace App\Http\Controllers;

use App\Jobs\ScoringJob;
use App\Models\AuditLog;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LoanApplicationController extends Controller
{
    public function index()
    {
        $applications = Auth::user()->loanApplications()
            ->with(['scoringResult', 'loanDecision', 'llmInsight'])
            ->latest()
            ->get();

        return view('borrower.applications.index', compact('applications'));
    }

    public function create()
    {
        $products = config('loan_products');

        return view('borrower.applications.create', compact('products'));
    }

    public function store(Request $request)
    {
        $products = config('loan_products');

        $validated = $request->validate([
            'person_age'            => 'required|integer|min:18|max:100',
            'person_income'         => 'required|integer|min:1',
            'person_home_ownership' => 'required|in:RENT,OWN,MORTGAGE,OTHER',
            'person_emp_length'     => 'nullable|numeric|min:0|max:60',
            'loan_intent'           => 'required|in:PERSONAL,EDUCATION,MEDICAL,VENTURE,HOMEIMPROVEMENT,DEBTCONSOLIDATION',
            'loan_product'          => ['required', Rule::in(array_keys($products))],
            'loan_amnt'             => 'required|integer|min:500',
            'loan_tenor_months'     => 'required|integer',
        ]);

        $product = $products[$validated['loan_product']];

        if (!in_array((int) $validated['loan_tenor_months'], $product['tenors'], true)) {
            return back()->withInput()->withErrors([
                'loan_tenor_months' => 'Jangka waktu cicilan tidak tersedia untuk produk ini.',
            ]);
        }

        if ($validated['loan_amnt'] > $product['limit']) {
            return back()->withInput()->withErrors([
                'loan_amnt' => 'Jumlah pinjaman melebihi limit produk ini (maks. Rp '.number_format($product['limit']).').',
            ]);
        }

        $validated['loan_int_rate'] = $product['rate'];
        $validated['user_id']       = Auth::id();
        $validated['status']        = 'pending';

        // Auto-compute loan_percent_income
        if ($validated['person_income'] > 0) {
            $validated['loan_percent_income'] = round(
                $validated['loan_amnt'] / $validated['person_income'], 4
            );
        }

        $app = LoanApplication::create($validated);

        AuditLog::create([
            'user_id'             => Auth::id(),
            'loan_application_id' => $app->id,
            'action'              => 'application.submitted',
            'actor_role'          => 'borrower',
            'ip_address'          => $request->ip(),
            'payload'             => ['loan_amnt' => $app->loan_amnt, 'loan_intent' => $app->loan_intent],
        ]);

        return redirect()->route('borrower.applications.show', $app)
            ->with('success', 'Pengajuan pinjaman berhasil dikirim. Menunggu verifikasi admin.');
    }

    public function show(LoanApplication $application)
    {
        $this->authorizeApp($application);

        $application->load(['scoringResult', 'loanDecision', 'llmInsight']);

        return view('borrower.applications.show', compact('application'));
    }

    private function authorizeApp(LoanApplication $app): void
    {
        if ($app->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
