<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_delete_accounts(): void
    {
        $account = User::factory()->create();

        $this->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => $account->id]);
    }

    public function test_authenticated_users_can_delete_another_account(): void
    {
        $user = User::factory()->create();
        $account = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'))
            ->assertInertiaFlash('success', 'アカウントを削除しました。');

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_users_can_delete_accounts_without_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        $account = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
    }

    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $user))
            ->assertRedirect(route('accounts.index'))
            ->assertInertiaFlash('error', 'ログイン中の自分のアカウントは削除できません。');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
