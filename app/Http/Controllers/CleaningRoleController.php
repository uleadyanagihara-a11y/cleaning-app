<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCleaningRoleRequest;
use App\Http\Requests\UpdateCleaningRoleRequest;
use App\Models\CleaningRole;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CleaningRoleController extends Controller
{
    /**
     * Display a listing of the cleaning roles.
     */
    public function index(): Response
    {
        return Inertia::render('CleaningItems/Index', [
            'cleaningRoles' => CleaningRole::query()
                ->select([
                    'id',
                    'name',
                    'description',
                    'required_member_count',
                    'is_active',
                ])
                ->withCount([
                    'cleaningAssignments as assignment_count',
                    'availableMembers as available_member_count',
                ])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (CleaningRole $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'required_member_count' => $role->required_member_count,
                    'is_active' => $role->is_active,
                    'assignment_count' => $role->assignment_count,
                    'available_member_count' => $role->available_member_count,
                    'can_delete' => $role->assignment_count === 0
                        && $role->available_member_count === 0,
                ]),
        ]);
    }

    /**
     * Store a newly created cleaning role.
     */
    public function store(
        StoreCleaningRoleRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        CleaningRole::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'required_member_count' => $validated['required_member_count'],
            'is_active' => true,
        ]);

        Inertia::flash('success', '役割を登録しました。');

        return to_route('cleaning-items.index');
    }

    /**
     * Update the specified cleaning role.
     */
    public function update(
        UpdateCleaningRoleRequest $request,
        CleaningRole $cleaningRole,
    ): RedirectResponse {
        $validated = $request->validated();

        $cleaningRole->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'required_member_count' => $validated['required_member_count'],
            'is_active' => $validated['is_active'],
        ]);

        Inertia::flash('success', '役割を更新しました。');

        return to_route('cleaning-items.index');
    }

    /**
     * Remove the specified cleaning role when it is not in use.
     */
    public function destroy(CleaningRole $cleaningRole): RedirectResponse
    {
        try {
            $usage = DB::transaction(function () use ($cleaningRole): ?array {
                $lockedRole = CleaningRole::query()
                    ->whereKey($cleaningRole->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedRole->loadCount([
                    'cleaningAssignments as assignment_count',
                    'availableMembers as available_member_count',
                ]);

                if (
                    $lockedRole->assignment_count > 0
                    || $lockedRole->available_member_count > 0
                ) {
                    return [
                        'assignment_count' => $lockedRole->assignment_count,
                        'available_member_count' => $lockedRole->available_member_count,
                    ];
                }

                $lockedRole->delete();

                return null;
            });
        } catch (QueryException) {
            Inertia::flash(
                'error',
                'この役割は使用中のため削除できません。無効に変更してください。',
            );

            return to_route('cleaning-items.index');
        }

        if ($usage !== null) {
            Inertia::flash('error', $this->inUseMessage($usage));

            return to_route('cleaning-items.index');
        }

        Inertia::flash('success', '役割を削除しました。');

        return to_route('cleaning-items.index');
    }

    /**
     * @param  array{assignment_count: int, available_member_count: int}  $usage
     */
    private function inUseMessage(array $usage): string
    {
        $details = [];

        if ($usage['assignment_count'] > 0) {
            $details[] = "清掃割当{$usage['assignment_count']}件";
        }

        if ($usage['available_member_count'] > 0) {
            $details[] = "担当可能メンバー{$usage['available_member_count']}名";
        }

        return 'この役割は'.implode('、', $details)
            .'で使用されているため削除できません。無効に変更してください。';
    }
}
