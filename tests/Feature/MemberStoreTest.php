<?php

namespace Tests\Feature;

use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_store_members(): void
    {
        $this->post(route('members.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('members', 0);
    }

    public function test_unverified_users_cannot_store_members(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('members.store'), $this->validPayload())
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('members', 0);
    }

    public function test_verified_users_can_store_a_member_with_cleaning_roles(): void
    {
        $user = User::factory()->create();
        $entrance = CleaningRole::create(['name' => '玄関']);
        $hallway = CleaningRole::create(['name' => '廊下・階段']);

        $response = $this->actingAs($user)->post(route('members.store'), [
            'name' => '  山田 太郎  ',
            'notes' => '  平日のみ対応  ',
            'is_active' => true,
            'cleaning_role_ids' => [$entrance->id, $hallway->id],
        ]);

        $response
            ->assertRedirect(route('members.index'))
            ->assertInertiaFlash('success', 'メンバーを登録しました。');

        $member = Member::query()->sole();

        $this->assertSame('山田 太郎', $member->name);
        $this->assertSame('平日のみ対応', $member->notes);
        $this->assertTrue($member->is_active);
        $this->assertEqualsCanonicalizing(
            [$entrance->id, $hallway->id],
            $member->availableCleaningRoles()->pluck('cleaning_roles.id')->all(),
        );
    }

    public function test_cleaning_roles_and_notes_are_optional(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('members.store'), [
                'name' => '佐藤 花子',
                'notes' => '   ',
                'is_active' => false,
                'cleaning_role_ids' => [],
            ])
            ->assertRedirect(route('members.index'));

        $member = Member::query()->sole();

        $this->assertNull($member->notes);
        $this->assertFalse($member->is_active);
        $this->assertDatabaseCount('cleaning_role_member', 0);
    }

    public function test_member_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('members.store'), [
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

        $this->assertDatabaseCount('members', 0);
    }

    public function test_notes_must_be_a_string_when_present(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('members.store'), [
                ...$this->validPayload(),
                'notes' => ['invalid'],
            ])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('members', 0);
    }

    public function test_inactive_unknown_and_duplicate_cleaning_roles_are_rejected(): void
    {
        $user = User::factory()->create();
        $activeRole = CleaningRole::create(['name' => '玄関']);
        $inactiveRole = CleaningRole::create([
            'name' => '廊下',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->post(route('members.store'), [
                ...$this->validPayload(),
                'cleaning_role_ids' => [
                    $activeRole->id,
                    $activeRole->id,
                    $inactiveRole->id,
                    999999,
                ],
            ])
            ->assertSessionHasErrors([
                'cleaning_role_ids.1',
                'cleaning_role_ids.2',
                'cleaning_role_ids.3',
            ]);

        $this->assertDatabaseCount('members', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => '山田 太郎',
            'notes' => null,
            'is_active' => true,
            'cleaning_role_ids' => [],
        ];
    }
}
