<?php

namespace Tests\Feature;

use App\Models\CleaningRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningRoleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_store_cleaning_roles(): void
    {
        $this->post(route('cleaning-items.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('cleaning_roles', 0);
    }

    public function test_unverified_users_cannot_store_cleaning_roles(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('cleaning-items.store'), $this->validPayload())
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('cleaning_roles', 0);
    }

    public function test_verified_users_can_store_a_cleaning_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('cleaning-items.store'),
            [
                'name' => '  会議室  ',
                'description' => '  机の拭き掃除と床の掃除  ',
                'required_member_count' => 3,
            ],
        );

        $response
            ->assertRedirect(route('cleaning-items.index'))
            ->assertInertiaFlash('success', '役割を登録しました。');

        $role = CleaningRole::query()->sole();

        $this->assertSame('会議室', $role->name);
        $this->assertSame('机の拭き掃除と床の掃除', $role->description);
        $this->assertSame(3, $role->required_member_count);
        $this->assertTrue($role->is_active);
    }

    public function test_description_is_optional_and_blank_is_stored_as_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cleaning-items.store'), [
                ...$this->validPayload(),
                'description' => '   ',
            ])
            ->assertRedirect(route('cleaning-items.index'));

        $this->assertNull(CleaningRole::query()->sole()->description);
    }

    public function test_cleaning_role_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cleaning-items.store'), [
                'name' => '   ',
                'description' => str_repeat('あ', 2001),
            ])
            ->assertSessionHasErrors([
                'name',
                'description',
                'required_member_count',
            ]);

        $this->assertDatabaseCount('cleaning_roles', 0);
    }

    public function test_name_and_description_must_be_strings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cleaning-items.store'), [
                ...$this->validPayload(),
                'name' => ['invalid'],
                'description' => ['invalid'],
            ])
            ->assertSessionHasErrors(['name', 'description']);

        $this->assertDatabaseCount('cleaning_roles', 0);
    }

    public function test_role_name_must_be_unique_after_trimming(): void
    {
        $user = User::factory()->create();
        CleaningRole::create(['name' => '玄関']);

        $this->actingAs($user)
            ->post(route('cleaning-items.store'), [
                ...$this->validPayload(),
                'name' => '  玄関  ',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('cleaning_roles', 1);
    }

    public function test_required_member_count_must_be_an_integer_between_one_and_99(): void
    {
        $user = User::factory()->create();

        foreach ([0, -1, 100, 1.5, 'invalid'] as $invalidCount) {
            $this->actingAs($user)
                ->post(route('cleaning-items.store'), [
                    ...$this->validPayload(),
                    'required_member_count' => $invalidCount,
                ])
                ->assertSessionHasErrors('required_member_count');
        }

        $this->assertDatabaseCount('cleaning_roles', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => '玄関',
            'description' => null,
            'required_member_count' => 1,
        ];
    }
}
