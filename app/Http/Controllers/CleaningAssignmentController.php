<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewCleaningAssignmentsRequest;
use App\Http\Requests\StoreCleaningAssignmentsRequest;
use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Services\AssignmentSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CleaningAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        // validation date
        $validated = Validator::make(
            ['date' => $request->query('date', now()->toDateString())],
            ['date' => ['required', 'date_format:Y-m-d']],
            [
                'date.required' => '対象日を入力してください。',
                'date.date_format' => '対象日は年-月-日の形式で入力してください。',
            ],
        )->validate();

        // 掃除当番の特定の日をメンバーとそのID、掃除当番ID,名前、必要人数取得
        $assignments = CleaningAssignment::query()
            ->where('assignment_date', $validated['date'])
            ->with(['member:id,name', 'cleaningRole:id,name,required_member_count'])
            ->orderBy('cleaning_role_id')
            ->orderBy('member_id')
            ->get();

        // 割当済掃除当番がなければ空のコレクション、あれば有効な掃除当番または割当られている掃除当番ID
        $existingAssignmentRoles = $assignments->isEmpty()
            ? collect()
            : CleaningRole::query()
                ->where('is_active', true)
                ->orWhereIn('id', $assignments->pluck('cleaning_role_id'))
                ->orderBy('id')
                ->get();

        return Inertia::render('CleaningAssignments/Index', [
            'selectedDate' => $validated['date'],
            'existingAssignments' => $existingAssignmentRoles
                ->map(function (CleaningRole $role) use ($assignments): array {
                    $roleAssignments = $assignments->where(
                        'cleaning_role_id',
                        $role->id,
                    );
                    $assignedCount = $roleAssignments->count();

                    return [
                        'cleaning_role_id' => $role->id,
                        'name' => $role->name,
                        'required_member_count' => $role->required_member_count,
                        'assignments' => $roleAssignments
                            ->map(fn (CleaningAssignment $assignment) => [
                                'member_id' => $assignment->member->id,
                                'name' => $assignment->member->name,
                            ])
                            ->values(),
                        'assigned_member_count' => $assignedCount,
                        'shortage_count' => max(
                            0,
                            $role->required_member_count - $assignedCount,
                        ),
                    ];
                })
                ->values(),
            'activeMembers' => Member::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name']),
            'hasActiveRoles' => CleaningRole::query()
                ->where('is_active', true)
                ->exists(),
        ]);
    }

    public function preview(
        PreviewCleaningAssignmentsRequest $request,
        AssignmentSelectionService $selectionService,
    ): JsonResponse {
        $validated = $request->validated();

        if (CleaningAssignment::query()
            ->where('assignment_date', $validated['assignment_date'])
            ->exists()) {
            throw ValidationException::withMessages([
                'assignment_date' => 'この対象日の掃除当番は確定済みです。',
            ]);
        }

        return response()->json($selectionService->select(
            $validated['assignment_date'],
            $validated['excluded_member_ids'] ?? [],
        ));
    }

    public function store(
        StoreCleaningAssignmentsRequest $request,
        AssignmentSelectionService $selectionService,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use (
            $request,
            $validated,
            $selectionService,
        ): void {
            Member::query()->orderBy('id')->lockForUpdate()->get(['id']);
            CleaningRole::query()->orderBy('id')->lockForUpdate()->get(['id']);

            if (CleaningAssignment::query()
                ->where('assignment_date', $validated['assignment_date'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'assignment_date' => 'この対象日の掃除当番は確定済みです。',
                ]);
            }

            $selection = $selectionService->select(
                $validated['assignment_date'],
                $validated['excluded_member_ids'] ?? [],
            );
            $expectedPairs = $this->selectionPairs($selection['roles']);
            $submittedPairs = collect($validated['assignments'])
                ->map(fn (array $assignment): string => $this->pairKey(
                    (int) $assignment['member_id'],
                    (int) $assignment['cleaning_role_id'],
                ))
                ->sort()
                ->values()
                ->all();

            if ($expectedPairs !== $submittedPairs) {
                throw ValidationException::withMessages([
                    'assignments' => '候補条件が変更されました。もう一度自動選択してください。',
                ]);
            }

            foreach ($selection['roles'] as $role) {
                foreach ($role['assignments'] as $assignment) {
                    CleaningAssignment::query()->create([
                        'member_id' => $assignment['member_id'],
                        'cleaning_role_id' => $role['cleaning_role_id'],
                        'assignment_date' => $validated['assignment_date'],
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

        Inertia::flash('success', '掃除当番を確定しました。');

        return to_route('cleaning-assignments.index', [
            'date' => $validated['assignment_date'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $roles
     * @return array<int, string>
     */
    private function selectionPairs(array $roles): array
    {
        $pairs = [];

        foreach ($roles as $role) {
            foreach ($role['assignments'] as $assignment) {
                $pairs[] = $this->pairKey(
                    $assignment['member_id'],
                    $role['cleaning_role_id'],
                );
            }
        }

        sort($pairs);

        return $pairs;
    }

    private function pairKey(int $memberId, int $roleId): string
    {
        return "{$roleId}:{$memberId}";
    }
}
