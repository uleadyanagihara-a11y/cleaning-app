<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\CleaningRole;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'cleaningRoles' => CleaningRole::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
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

    /**
     * Store a newly created member.
     */
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $member = Member::query()->create([
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            $member->availableCleaningRoles()->sync(
                $validated['cleaning_role_ids'] ?? [],
            );
        });

        Inertia::flash('success', 'メンバーを登録しました。');

        return to_route('members.index');
    }

    /**
     * Update the specified member.
     */
    public function update(
        UpdateMemberRequest $request,
        Member $member,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($member, $validated): void {
            $member->update([
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            $member->availableCleaningRoles()->sync(
                $validated['cleaning_role_ids'] ?? [],
            );
        });

        Inertia::flash('success', 'メンバー情報を更新しました。');

        return to_route('members.index');
    }

    /**
     * Remove the specified member.
     */
    public function destroy(Member $member): RedirectResponse
    {
        DB::transaction(function () use ($member): void {
            $member->cleaningAssignments()->delete();
            $member->delete();
        });

        Inertia::flash('success', 'メンバーを削除しました。');

        return to_route('members.index');
    }
}
