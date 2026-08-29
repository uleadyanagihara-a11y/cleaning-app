<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Services\AssignmentSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentSelectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_fills_the_maximum_number_of_slots(): void
    {
        $entrance = CleaningRole::create(['name' => '玄関']);
        $toilet = CleaningRole::create(['name' => 'トイレ']);
        $versatileMember = Member::create(['name' => '両方担当可能']);
        $entranceOnlyMember = Member::create(['name' => '玄関のみ']);

        $versatileMember->availableCleaningRoles()->attach([
            $entrance->id,
            $toilet->id,
        ]);
        $entranceOnlyMember->availableCleaningRoles()->attach($entrance);

        $selection = app(AssignmentSelectionService::class)->select(
            '2026-08-27',
        );

        $this->assertSame(2, $selection['assigned_member_count']);
        $this->assertSame(0, $selection['shortage_count']);
        $this->assertSame(
            [$entranceOnlyMember->id],
            array_column($selection['roles'][0]['assignments'], 'member_id'),
        );
        $this->assertSame(
            [$versatileMember->id],
            array_column($selection['roles'][1]['assignments'], 'member_id'),
        );
    }

    public function test_selection_prefers_a_member_with_fewer_previous_assignments(): void
    {
        $role = CleaningRole::create(['name' => '玄関']);
        $experiencedMember = Member::create(['name' => '担当済み']);
        $newMember = Member::create(['name' => '未担当']);
        $experiencedMember->availableCleaningRoles()->attach($role);
        $newMember->availableCleaningRoles()->attach($role);

        CleaningAssignment::create([
            'member_id' => $experiencedMember->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-26',
        ]);

        $selection = app(AssignmentSelectionService::class)->select(
            '2026-08-27',
        );

        $this->assertSame(
            $newMember->id,
            $selection['roles'][0]['assignments'][0]['member_id'],
        );
    }

    public function test_selection_excludes_inactive_and_explicitly_excluded_members(): void
    {
        $role = CleaningRole::create(['name' => '玄関']);
        $excludedMember = Member::create(['name' => '欠席者']);
        $inactiveMember = Member::create([
            'name' => '無効メンバー',
            'is_active' => false,
        ]);
        $excludedMember->availableCleaningRoles()->attach($role);
        $inactiveMember->availableCleaningRoles()->attach($role);

        $selection = app(AssignmentSelectionService::class)->select(
            '2026-08-27',
            [$excludedMember->id],
        );

        $this->assertSame(0, $selection['assigned_member_count']);
        $this->assertSame(1, $selection['shortage_count']);
        $this->assertSame([], $selection['roles'][0]['assignments']);
    }

    public function test_a_member_is_selected_for_only_one_role_per_day(): void
    {
        $firstRole = CleaningRole::create(['name' => '玄関']);
        $secondRole = CleaningRole::create(['name' => 'トイレ']);
        $member = Member::create(['name' => '田中']);
        $member->availableCleaningRoles()->attach([
            $firstRole->id,
            $secondRole->id,
        ]);

        $selection = app(AssignmentSelectionService::class)->select(
            '2026-08-27',
        );

        $this->assertSame(1, $selection['assigned_member_count']);
        $this->assertSame(1, $selection['shortage_count']);
    }
}
