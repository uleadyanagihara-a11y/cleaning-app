<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberUpdateDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_unverified_users_cannot_update_or_delete_members(): void
    {
        $member = Member::create(['name' => '変更前']);

        $this->patch(route('members.update', $member), $this->validPayload())
            ->assertRedirect(route('login'));
        $this->delete(route('members.destroy', $member))
            ->assertRedirect(route('login'));

        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)
            ->patch(route('members.update', $member), $this->validPayload())
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($unverifiedUser)
            ->delete(route('members.destroy', $member))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => '変更前',
        ]);
    }

    public function test_verified_users_can_update_a_member_and_cleaning_roles(): void
    {
        $user = User::factory()->create();
        $oldRole = CleaningRole::create(['name' => '玄関']);
        $newRole = CleaningRole::create(['name' => '廊下']);
        $member = Member::create([
            'name' => '変更前',
            'notes' => '変更前の備考',
            'is_active' => true,
        ]);
        $member->availableCleaningRoles()->attach($oldRole);

        $response = $this->actingAs($user)
            ->patch(route('members.update', $member), [
                'name' => '  変更後  ',
                'notes' => '  変更後の備考  ',
                'is_active' => false,
                'cleaning_role_ids' => [$newRole->id],
            ]);

        $response
            ->assertRedirect(route('members.index'))
            ->assertInertiaFlash('success', 'メンバー情報を更新しました。');

        $member->refresh();

        $this->assertSame('変更後', $member->name);
        $this->assertSame('変更後の備考', $member->notes);
        $this->assertFalse($member->is_active);
        $this->assertSame(
            [$newRole->id],
            $member->availableCleaningRoles()->pluck('cleaning_roles.id')->all(),
        );
    }

    public function test_member_update_is_validated(): void
    {
        $user = User::factory()->create();
        $member = Member::create(['name' => '変更前']);

        $this->actingAs($user)
            ->patch(route('members.update', $member), [
                'name' => '   ',
                'notes' => str_repeat('あ', 2001),
                'cleaning_role_ids' => 'invalid',
            ])
            ->assertSessionHasErrors([
                'name',
                'notes',
                'is_active',
                'cleaning_role_ids',
            ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => '変更前',
        ]);
    }

    public function test_verified_users_can_delete_a_member_and_its_role_links(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '削除対象']);
        $member->availableCleaningRoles()->attach($role);

        $response = $this->actingAs($user)
            ->delete(route('members.destroy', $member));

        $response
            ->assertRedirect(route('members.index'))
            ->assertInertiaFlash('success', 'メンバーを削除しました。');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('cleaning_role_member', [
            'member_id' => $member->id,
        ]);
    }

    public function test_members_with_assignment_history_are_deleted_with_their_history(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '履歴あり']);
        CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('members.destroy', $member));

        $response
            ->assertRedirect(route('members.index'))
            ->assertInertiaFlash('success', 'メンバーを削除しました。');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('cleaning_assignments', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => '変更後',
            'notes' => null,
            'is_active' => true,
            'cleaning_role_ids' => [],
        ];
    }
}
