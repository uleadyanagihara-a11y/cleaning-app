<?php

namespace App\Services;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CleaningAssignmentPdfService
{
    /**
     * @return array{
     *     assignment_date: CarbonImmutable,
     *     roles: array<int, array{
     *         cleaning_role_id: int,
     *         name: string,
     *         required_member_count: int,
     *         assigned_member_count: int,
     *         shortage_count: int,
     *         member_names: array<int, string>
     *     }>,
     *     assigned_member_count: int,
     *     required_member_count: int,
     *     shortage_count: int
     * }
     */
    public function build(string $date): array
    {
        $assignments = CleaningAssignment::query()
            ->where('assignment_date', $date)
            ->with([
                'member:id,name',
                'cleaningRole:id,name,required_member_count',
            ])
            ->orderBy('cleaning_role_id')
            ->orderBy('member_id')
            ->get();

        $assignmentRoleIds = $assignments->pluck('cleaning_role_id');
        $roles = $assignments->isEmpty()
            ? collect()
            : CleaningRole::query()
                ->where('is_active', true)
                ->orWhereIn('id', $assignmentRoleIds)
                ->orderBy('id')
                ->get();
        $roleRows = $roles
            ->map(function (CleaningRole $role) use ($assignments): array {
                /** @var Collection<int, CleaningAssignment> $roleAssignments */
                $roleAssignments = $assignments->where(
                    'cleaning_role_id',
                    $role->id,
                );
                $assignedCount = $roleAssignments->count();

                return [
                    'cleaning_role_id' => $role->id,
                    'name' => $role->name,
                    'required_member_count' => $role->required_member_count,
                    'assigned_member_count' => $assignedCount,
                    'shortage_count' => max(
                        0,
                        $role->required_member_count - $assignedCount,
                    ),
                    'member_names' => $roleAssignments
                        ->map(
                            fn (CleaningAssignment $assignment): string => $assignment->member->name,
                        )
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'assignment_date' => CarbonImmutable::parse($date)->startOfDay(),
            'roles' => $roleRows,
            'assigned_member_count' => $assignments->count(),
            'required_member_count' => array_sum(array_column(
                $roleRows,
                'required_member_count',
            )),
            'shortage_count' => array_sum(array_column(
                $roleRows,
                'shortage_count',
            )),
        ];
    }
}
