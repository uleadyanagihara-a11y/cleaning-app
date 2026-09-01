<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['verified', 'unverified'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $accounts = User::query()
            ->when($search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->when($status === 'verified', fn (Builder $query) => $query
                ->whereNotNull('email_verified_at'))
            ->when($status === 'unverified', fn (Builder $query) => $query
                ->whereNull('email_verified_at'))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'email_verified_at' => $account->email_verified_at?->toISOString(),
                'created_at' => $account->created_at?->toISOString(),
                'is_current' => $account->is($request->user()),
            ]);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'counts' => [
                'all' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'unverified' => User::query()->whereNull('email_verified_at')->count(),
            ],
        ]);
    }
}
