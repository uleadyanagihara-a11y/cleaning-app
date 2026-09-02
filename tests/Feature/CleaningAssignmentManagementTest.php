<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use App\Services\AssignmentSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CleaningAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_cleaning_assignment_endpoints(): void
    {
        $this->get(route('cleaning-assignments.index'))
            ->assertRedirect(route('login'));
        $this->postJson(route('cleaning-assignments.preview'), [
            'assignment_date' => '2026-08-27',
        ])->assertUnauthorized();
        $this->post(route('cleaning-assignments.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_active_members_and_existing_assignments(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create([
            'name' => '玄関',
            'required_member_count' => 2,
        ]);
        $member = Member::create(['name' => '田中']);
        Member::create(['name' => '無効', 'is_active' => false]);
        CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-27',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('cleaning-assignments.index', [
                'date' => '2026-08-27',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CleaningAssignments/Index')
                ->where('selectedDate', '2026-08-27')
                ->has('activeMembers', 1)
                ->where('activeMembers.0.name', '田中')
                ->has('existingAssignments', 1)
                ->where('existingAssignments.0.name', '玄関')
                ->where('existingAssignments.0.assigned_member_count', 1)
                ->where('existingAssignments.0.shortage_count', 1));
    }

    public function test_preview_returns_assignments_and_shortages(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create([
            'name' => '玄関',
            'required_member_count' => 2,
        ]);
        $member = Member::create(['name' => '田中']);
        $member->availableCleaningRoles()->attach($role);

        $this->actingAs($user)
            ->postJson(route('cleaning-assignments.preview'), [
                'assignment_date' => '2026-08-27',
                'excluded_member_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('assigned_member_count', 1)
            ->assertJsonPath('required_member_count', 2)
            ->assertJsonPath('shortage_count', 1)
            ->assertJsonPath('roles.0.assignments.0.name', '田中');
    }

    public function test_authenticated_users_can_confirm_the_current_preview(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '田中']);
        $member->availableCleaningRoles()->attach($role);
        $selection = app(AssignmentSelectionService::class)->select(
            '2026-08-27',
        );

        $response = $this->actingAs($user)->post(
            route('cleaning-assignments.store'),
            [
                'assignment_date' => '2026-08-27',
                'excluded_member_ids' => [],
                'assignments' => $this->flattenSelection($selection['roles']),
            ],
        );

        $response
            ->assertRedirect(route('cleaning-assignments.index', [
                'date' => '2026-08-27',
            ]))
            ->assertInertiaFlash('success', '掃除当番を確定しました。');

        $this->assertDatabaseHas('cleaning_assignments', [
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-27',
            'created_by' => $user->id,
        ]);
    }

    public function test_confirm_rejects_a_result_that_differs_from_the_current_selection(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $selectedMember = Member::create(['name' => '候補1']);
        $otherMember = Member::create(['name' => '候補2']);
        $selectedMember->availableCleaningRoles()->attach($role);
        $otherMember->availableCleaningRoles()->attach($role);

        $this->actingAs($user)
            ->from(route('cleaning-assignments.index'))
            ->post(route('cleaning-assignments.store'), [
                'assignment_date' => '2026-08-27',
                'excluded_member_ids' => [],
                'assignments' => [[
                    'member_id' => $otherMember->id,
                    'cleaning_role_id' => $role->id,
                ]],
            ])
            ->assertRedirect(route('cleaning-assignments.index'))
            ->assertSessionHasErrors('assignments');

        $this->assertDatabaseCount('cleaning_assignments', 0);
    }

    public function test_an_existing_date_cannot_be_previewed_or_confirmed_again(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '田中']);
        $member->availableCleaningRoles()->attach($role);
        CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-27',
        ]);

        $payload = [
            'assignment_date' => '2026-08-27',
            'excluded_member_ids' => [],
            'assignments' => [[
                'member_id' => $member->id,
                'cleaning_role_id' => $role->id,
            ]],
        ];

        $this->actingAs($user)
            ->postJson(route('cleaning-assignments.preview'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignment_date');

        $this->actingAs($user)
            ->post(route('cleaning-assignments.store'), $payload)
            ->assertSessionHasErrors('assignment_date');

        $this->assertDatabaseCount('cleaning_assignments', 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $roles
     * @return array<int, array{member_id: int, cleaning_role_id: int}>
     */
    private function flattenSelection(array $roles): array
    {
        $assignments = [];

        foreach ($roles as $role) {
            foreach ($role['assignments'] as $assignment) {
                $assignments[] = [
                    'member_id' => $assignment['member_id'],
                    'cleaning_role_id' => $role['cleaning_role_id'],
                ];
            }
        }

        return $assignments;
    }
}
