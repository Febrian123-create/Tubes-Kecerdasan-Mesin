<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

// ── Borrower routes ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:borrower'])->prefix('borrower')->name('borrower.')->group(function () {
    Route::get('/dashboard', [LoanApplicationController::class, 'index'])->name('dashboard');
    Route::resource('applications', LoanApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
});

// ── Admin routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('applications', AdminApplicationController::class)->only(['index', 'show']);
});

// ── Shared after-login redirect ───────────────────────────────────────────────
Route::get('/dashboard', function () {
    return auth()->user()?->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('borrower.dashboard');
})->middleware('auth')->name('dashboard');

// ── Profile (Breeze) ─────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
