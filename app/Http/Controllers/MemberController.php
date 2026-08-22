<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the members.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $members = Member::query()
            ->with(['availableCleaningRoles' => fn ($query) => $query
                ->orderBy('cleaning_roles.name')])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when($status !== '', fn (Builder $query) => $query
                ->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'notes' => $member->notes,
                'is_active' => $member->is_active,
                'available_cleaning_roles' => $member->availableCleaningRoles
                    ->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ])
                    ->values(),
            ]);

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'counts' => [
                'all' => Member::query()->count(),
                'active' => Member::query()->where('is_active', true)->count(),
                'inactive' => Member::query()->where('is_active', false)->count(),
            ],
        ]);
    }
}
