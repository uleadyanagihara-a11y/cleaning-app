<?php

namespace App\Services;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * 担当可能な組み合わせと過去の割当履歴から、対象日の掃除当番を選定する。
 *
 * 1人1役の制約を守りながら最大マッチングを行い、割当可能な枠数を最大化する。
 * 候補者が不足する場合は同じ人を重複させず、未充足の枠を不足数として返す。
 */
class AssignmentSelectionService
{
    /**
     * 対象日より前の履歴を使い、公平性を考慮した最大件数の割当を返す。
     *
     * @param  array<int, int>  $excludedMemberIds
     * @return array{
     *     roles: array<int, array{
     *         cleaning_role_id: int,
     *         name: string,
     *         required_member_count: int,
     *         assignments: array<int, array{member_id: int, name: string}>,
     *         assigned_member_count: int,
     *         shortage_count: int
     *     }>,
     *     assigned_member_count: int,
     *     required_member_count: int,
     *     shortage_count: int
     * }
     */
    public function select(
        string $assignmentDate,
        array $excludedMemberIds = [],
    ): array {
        // 比較やクエリ条件が安定するよう、除外IDを整数化して重複を除く。
        $excludedMemberIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $excludedMemberIds,
        )));

        // 無効な役割・メンバーと、当日除外されたメンバーは候補に含めない。
        $roles = CleaningRole::query()
            ->where('is_active', true)
            ->with(['availableMembers' => fn ($query) => $query
                ->where('members.is_active', true)
                ->when(
                    $excludedMemberIds !== [],
                    fn ($query) => $query->whereNotIn(
                        'members.id',
                        $excludedMemberIds,
                    ),
                )
                ->orderBy('members.id')])
            ->orderBy('id')
            ->get();

        // 対象日当日の割当や未来の予定は、公平性の計算に影響させない。
        $overallStats = CleaningAssignment::query()
            ->where('assignment_date', '<', $assignmentDate)
            ->select('member_id')
            ->selectRaw('COUNT(*) as assignment_count')
            ->selectRaw('MAX(assignment_date) as last_assignment_date')
            ->groupBy('member_id')
            ->get()
            ->keyBy('member_id');

        // 全体の担当回数とは別に、同じ役割へ偏らないための履歴も集計する。
        $roleStats = CleaningAssignment::query()
            ->where('assignment_date', '<', $assignmentDate)
            ->select(['member_id', 'cleaning_role_id'])
            ->selectRaw('COUNT(*) as assignment_count')
            ->selectRaw('MAX(assignment_date) as last_assignment_date')
            ->groupBy(['member_id', 'cleaning_role_id'])
            ->get()
            ->keyBy(fn (CleaningAssignment $assignment): string => $this->roleStatKey(
                $assignment->member_id,
                $assignment->cleaning_role_id,
            ));

        $members = $roles
            ->flatMap(fn (CleaningRole $role) => $role->availableMembers)
            ->unique('id')
            ->keyBy('id');

        [$slots, $candidateIdsBySlot] = $this->buildSlots(
            $roles,
            $overallStats,
            $roleStats,
        );

        // 担当可能者が少ない枠から処理し、専門性の高いメンバーを先に確保する。
        $orderedSlotIds = array_keys($slots);
        usort($orderedSlotIds, fn (int $first, int $second): int => [
            count($candidateIdsBySlot[$first]),
            $slots[$first]['cleaning_role_id'],
            $slots[$first]['slot_number'],
        ] <=> [
            count($candidateIdsBySlot[$second]),
            $slots[$second]['cleaning_role_id'],
            $slots[$second]['slot_number'],
        ]);

        // 双方向の対応を保持し、再割当時にも1人1役を保証する。
        $memberToSlot = [];
        $slotToMember = [];

        foreach ($orderedSlotIds as $slotId) {
            $seenMemberIds = [];
            $this->matchSlot(
                $slotId,
                $candidateIdsBySlot,
                $seenMemberIds,
                $memberToSlot,
                $slotToMember,
            );
        }

        return $this->formatSelection($roles, $slots, $slotToMember, $members);
    }

    /**
     * 各役割を必要人数分の独立した割当枠へ展開する。
     *
     * 各枠の候補者は公平性の高い順に並べ、最大マッチング探索時の
     * 優先順位として使用する。
     *
     * @param  Collection<int, CleaningRole>  $roles
     * @param  Collection<int, CleaningAssignment>  $overallStats
     * @param  Collection<string, CleaningAssignment>  $roleStats
     * @return array{
     *     array<int, array{cleaning_role_id: int, slot_number: int}>,
     *     array<int, array<int, int>>
     * }
     */
    private function buildSlots(
        Collection $roles,
        Collection $overallStats,
        Collection $roleStats,
    ): array {
        $slots = [];
        $candidateIdsBySlot = [];
        $slotId = 0;

        foreach ($roles as $role) {
            $candidateIds = $role->availableMembers
                ->sort(function (Member $first, Member $second) use (
                    $role,
                    $overallStats,
                    $roleStats,
                ): int {
                    return $this->fairnessRank(
                        $first,
                        $role,
                        $overallStats,
                        $roleStats,
                    ) <=> $this->fairnessRank(
                        $second,
                        $role,
                        $overallStats,
                        $roleStats,
                    );
                })
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all();

            for ($number = 1; $number <= $role->required_member_count; $number++) {
                $slots[$slotId] = [
                    'cleaning_role_id' => $role->id,
                    'slot_number' => $number,
                ];
                $candidateIdsBySlot[$slotId] = $candidateIds;
                $slotId++;
            }
        }

        return [$slots, $candidateIdsBySlot];
    }

    /**
     * 候補者を比較するための辞書順キーを返す。
     *
     * 総担当回数、同じ役割の担当回数、各最終担当日、メンバーIDの順に
     * 比較する。担当歴がない場合の日付は空文字となるため最優先になる。
     *
     * @param  Collection<int, CleaningAssignment>  $overallStats
     * @param  Collection<string, CleaningAssignment>  $roleStats
     * @return array{int, int, string, string, int}
     */
    private function fairnessRank(
        Member $member,
        CleaningRole $role,
        Collection $overallStats,
        Collection $roleStats,
    ): array {
        $overall = $overallStats->get($member->id);
        $roleStat = $roleStats->get($this->roleStatKey($member->id, $role->id));

        return [
            (int) ($overall?->assignment_count ?? 0),
            (int) ($roleStat?->assignment_count ?? 0),
            (string) ($overall?->last_assignment_date ?? ''),
            (string) ($roleStat?->last_assignment_date ?? ''),
            $member->id,
        ];
    }

    /**
     * 増加路を探索し、指定した枠へメンバーを割り当てる。
     *
     * 候補者が別の枠へ割当済みなら、その枠を再帰的に別候補へ移せるか
     * 試す。移動できれば空いた候補者を現在の枠へ割り当てる。
     *
     * @param  array<int, array<int, int>>  $candidateIdsBySlot
     * @param  array<int, bool>  $seenMemberIds
     * @param  array<int, int>  $memberToSlot
     * @param  array<int, int>  $slotToMember
     */
    private function matchSlot(
        int $slotId,
        array $candidateIdsBySlot,
        array &$seenMemberIds,
        array &$memberToSlot,
        array &$slotToMember,
    ): bool {
        foreach ($candidateIdsBySlot[$slotId] as $memberId) {
            if (isset($seenMemberIds[$memberId])) {
                continue;
            }

            $seenMemberIds[$memberId] = true;
            $assignedSlotId = $memberToSlot[$memberId] ?? null;

            if (
                $assignedSlotId === null
                || $this->matchSlot(
                    $assignedSlotId,
                    $candidateIdsBySlot,
                    $seenMemberIds,
                    $memberToSlot,
                    $slotToMember,
                )
            ) {
                $memberToSlot[$memberId] = $slotId;
                $slotToMember[$slotId] = $memberId;

                return true;
            }
        }

        return false;
    }

    /**
     * 最大マッチングの内部表現を、画面と保存処理で利用する形式へ変換する。
     *
     * 役割ごとの担当者数と不足数に加え、全役割の合計値も算出する。
     *
     * @param  Collection<int, CleaningRole>  $roles
     * @param  array<int, array{cleaning_role_id: int, slot_number: int}>  $slots
     * @param  array<int, int>  $slotToMember
     * @param  Collection<int, Member>  $members
     * @return array<string, mixed>
     */
    private function formatSelection(
        Collection $roles,
        array $slots,
        array $slotToMember,
        Collection $members,
    ): array {
        $assignmentsByRole = [];

        foreach ($slotToMember as $slotId => $memberId) {
            $roleId = $slots[$slotId]['cleaning_role_id'];
            $member = $members->get($memberId);

            $assignmentsByRole[$roleId][] = [
                'member_id' => $memberId,
                'name' => $member->name,
            ];
        }

        $requiredCount = 0;
        $assignedCount = 0;
        $formattedRoles = [];

        foreach ($roles as $role) {
            $assignments = $assignmentsByRole[$role->id] ?? [];
            usort(
                $assignments,
                static fn (array $first, array $second): int => $first['member_id'] <=> $second['member_id'],
            );

            $roleAssignedCount = count($assignments);
            $roleShortageCount = max(
                0,
                $role->required_member_count - $roleAssignedCount,
            );

            $formattedRoles[] = [
                'cleaning_role_id' => $role->id,
                'name' => $role->name,
                'required_member_count' => $role->required_member_count,
                'assignments' => $assignments,
                'assigned_member_count' => $roleAssignedCount,
                'shortage_count' => $roleShortageCount,
            ];

            $requiredCount += $role->required_member_count;
            $assignedCount += $roleAssignedCount;
        }

        return [
            'roles' => $formattedRoles,
            'assigned_member_count' => $assignedCount,
            'required_member_count' => $requiredCount,
            'shortage_count' => max(0, $requiredCount - $assignedCount),
        ];
    }

    private function roleStatKey(int $memberId, int $roleId): string
    {
        // メンバー別・役割別集計をCollectionから定数時間で参照するためのキー。
        return "{$memberId}:{$roleId}";
    }
}
