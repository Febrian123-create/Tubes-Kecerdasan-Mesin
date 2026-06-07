<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $transactions = $user->walletTransactions()
            ->with('loanApplication')
            ->latest()
            ->get();

        return view('borrower.wallet.index', [
            'user'         => $user,
            'transactions' => $transactions,
        ]);
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bank_name'      => 'required|string|max:60',
            'account_number' => 'required|string|max:30',
            'amount'         => 'required|integer|min:10000',
        ]);

        if ($validated['amount'] > $user->balance) {
            return back()->withInput()->withErrors([
                'amount' => 'Saldo Anda tidak mencukupi untuk transfer ini.',
            ]);
        }

        DB::transaction(function () use ($user, $validated) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            $locked->decrement('balance', $validated['amount']);

            WalletTransaction::create([
                'user_id'       => $locked->id,
                'type'          => 'debit',
                'amount'        => $validated['amount'],
                'balance_after' => $locked->balance,
                'description'   => 'Transfer ke '.$validated['bank_name'].' — '.$validated['account_number'],
            ]);
        });

        return redirect()->route('borrower.wallet.index')
            ->with('success', 'Dana berhasil ditransfer ke rekening tujuan.');
    }
}
