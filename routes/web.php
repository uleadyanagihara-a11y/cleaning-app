<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/members', [MemberController::class, 'index'])
        ->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])
        ->name('members.store');

    Route::get('/cleaning-items', function () {
        return Inertia::render('CleaningItems/Index');
    })->name('cleaning-items.index');

    Route::get('/pdf', function () {
        return Inertia::render('Pdf/Index');
    })->name('pdf.index');

    Route::get('/accounts', function () {
        return Inertia::render('Accounts/Index');
    })->name('accounts.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
