<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => '新規 担当者',
            'email' => 'newcomer@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ];
    }

    public function test_guests_cannot_store_accounts(): void
    {
        $this->post(route('accounts.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_users_can_store_accounts_without_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), $this->validPayload())
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseCount('users', 2);
    }

    public function test_authenticated_users_can_store_an_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            'name' => '  新規 担当者  ',
            'email' => '  newcomer@example.com  ',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $response
            ->assertRedirect(route('accounts.index'))
            ->assertInertiaFlash('success', 'アカウントを登録しました。');

        $account = User::query()->where('email', 'newcomer@example.com')->sole();

        $this->assertSame('新規 担当者', $account->name);
        $this->assertTrue(Hash::check('password1234', $account->password));
    }

    public function test_account_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => '   ',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_must_be_unique(): void
    {
        $user = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                ...$this->validPayload(),
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_must_be_lowercase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                ...$this->validPayload(),
                'email' => 'Newcomer@Example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }
}
