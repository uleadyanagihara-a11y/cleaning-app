<?php

namespace Tests\Feature;

use App\Models\CleaningAssignment;
use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningRoleUpdateDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_unverified_users_cannot_update_or_delete_cleaning_roles(): void
    {
        $role = CleaningRole::create(['name' => '変更前']);

        $this->patch(route('cleaning-items.update', $role), $this->validPayload())
            ->assertRedirect(route('login'));
        $this->delete(route('cleaning-items.destroy', $role))
            ->assertRedirect(route('login'));

        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)
            ->patch(route('cleaning-items.update', $role), $this->validPayload())
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($unverifiedUser)
            ->delete(route('cleaning-items.destroy', $role))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('cleaning_roles', [
            'id' => $role->id,
            'name' => '変更前',
        ]);
    }

    public function test_verified_users_can_update_a_cleaning_role(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create([
            'name' => '変更前',
            'description' => '変更前の説明',
            'required_member_count' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->patch(route('cleaning-items.update', $role), [
                'name' => '  変更後  ',
                'description' => '  変更後の説明  ',
                'required_member_count' => 3,
                'is_active' => false,
            ]);

        $response
            ->assertRedirect(route('cleaning-items.index'))
            ->assertInertiaFlash('success', '役割を更新しました。');

        $role->refresh();

        $this->assertSame('変更後', $role->name);
        $this->assertSame('変更後の説明', $role->description);
        $this->assertSame(3, $role->required_member_count);
        $this->assertFalse($role->is_active);
    }

    public function test_update_allows_the_current_name_but_rejects_another_role_name(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        CleaningRole::create(['name' => '廊下']);

        $this->actingAs($user)
            ->patch(route('cleaning-items.update', $role), [
                ...$this->validPayload(),
                'name' => '  玄関  ',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('cleaning-items.update', $role), [
                ...$this->validPayload(),
                'name' => '  廊下  ',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_verified_users_can_delete_an_unused_cleaning_role(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '未使用']);

        $this->actingAs($user)
            ->delete(route('cleaning-items.destroy', $role))
            ->assertRedirect(route('cleaning-items.index'))
            ->assertInertiaFlash('success', '役割を削除しました。');

        $this->assertDatabaseMissing('cleaning_roles', ['id' => $role->id]);
    }

    public function test_role_assigned_to_members_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '田中']);
        $member->availableCleaningRoles()->attach($role);

        $this->actingAs($user)
            ->delete(route('cleaning-items.destroy', $role))
            ->assertRedirect(route('cleaning-items.index'))
            ->assertInertiaFlash(
                'error',
                'この役割は担当可能メンバー1名で使用されているため削除できません。無効に変更してください。',
            );

        $this->assertDatabaseHas('cleaning_roles', ['id' => $role->id]);
        $this->assertDatabaseHas('cleaning_role_member', [
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
        ]);
    }

    public function test_role_with_assignment_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $member = Member::create(['name' => '田中']);
        CleaningAssignment::create([
            'member_id' => $member->id,
            'cleaning_role_id' => $role->id,
            'assignment_date' => '2026-08-26',
        ]);

        $this->actingAs($user)
            ->delete(route('cleaning-items.destroy', $role))
            ->assertRedirect(route('cleaning-items.index'))
            ->assertInertiaFlash(
                'error',
                'この役割は清掃割当1件で使用されているため削除できません。無効に変更してください。',
            );

        $this->assertDatabaseHas('cleaning_roles', ['id' => $role->id]);
        $this->assertDatabaseHas('cleaning_assignments', [
            'cleaning_role_id' => $role->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => '変更後',
            'description' => null,
            'required_member_count' => 1,
            'is_active' => true,
        ];
    }
}
