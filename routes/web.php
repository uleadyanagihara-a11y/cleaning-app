<?php

use App\Http\Controllers\CleaningAssignmentController;
use App\Http\Controllers\CleaningRoleController;
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
    Route::patch('/members/{member}', [MemberController::class, 'update'])
        ->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])
        ->name('members.destroy');

    Route::get('/cleaning-items', [CleaningRoleController::class, 'index'])
        ->name('cleaning-items.index');
    Route::post('/cleaning-items', [CleaningRoleController::class, 'store'])
        ->name('cleaning-items.store');
    Route::patch('/cleaning-items/{cleaningRole}', [CleaningRoleController::class, 'update'])
        ->name('cleaning-items.update');
    Route::delete('/cleaning-items/{cleaningRole}', [CleaningRoleController::class, 'destroy'])
        ->name('cleaning-items.destroy');

    Route::get('/cleaning-assignments', [CleaningAssignmentController::class, 'index'])
        ->name('cleaning-assignments.index');
    Route::post('/cleaning-assignments/preview', [CleaningAssignmentController::class, 'preview'])
        ->name('cleaning-assignments.preview');
    Route::post('/cleaning-assignments', [CleaningAssignmentController::class, 'store'])
        ->name('cleaning-assignments.store');

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
