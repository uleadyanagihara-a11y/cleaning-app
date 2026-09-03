<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        ]);

        $search = trim($validated['search'] ?? '');

        $accounts = User::query()
            ->when($search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'created_at' => $account->created_at?->toISOString(),
                'is_current' => $account->is($request->user()),
            ]);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
            ],
            'counts' => [
                'all' => User::query()->count(),
            ],
        ]);
    }
}
