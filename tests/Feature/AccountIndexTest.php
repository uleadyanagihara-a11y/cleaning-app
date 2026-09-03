<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_account_list(): void
    {
        $this->get(route('accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_users_can_view_the_account_list_without_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_accounts(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'ログインユーザー',
            'email' => 'current@example.com',
        ]);
        User::factory()->create([
            'name' => '別ユーザー',
            'email' => 'other@example.com',
        ]);

        $this->actingAs($currentUser)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounts/Index')
                ->where('counts.all', 2)
                ->where('filters.search', '')
                ->has('accounts.data', 2)
                ->where('accounts.data.0.name', 'ログインユーザー')
                ->where('accounts.data.0.email', 'current@example.com')
                ->where('accounts.data.0.is_current', true)
                ->where('accounts.data.1.name', '別ユーザー')
                ->where('accounts.data.1.is_current', false));
    }

    public function test_accounts_can_be_searched_by_name_or_email(): void
    {
        $currentUser = User::factory()->create();
        User::factory()->create([
            'name' => '検索対象',
            'email' => 'target@example.com',
        ]);
        User::factory()->create([
            'name' => '別ユーザー',
            'email' => 'other@example.com',
        ]);

        $this->actingAs($currentUser)
            ->get(route('accounts.index', [
                'search' => 'target@example.com',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'target@example.com')
                ->has('accounts.data', 1)
                ->where('accounts.data.0.name', '検索対象')
                ->where('accounts.total', 1)
                ->where('counts.all', 3));
    }

    public function test_account_list_is_paginated_twenty_accounts_at_a_time(): void
    {
        $currentUser = User::factory()->create();
        User::factory()->count(20)->create();

        $this->actingAs($currentUser)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('accounts.data', 20)
                ->where('accounts.current_page', 1)
                ->where('accounts.last_page', 2)
                ->where('accounts.per_page', 20)
                ->where('accounts.total', 21));
    }
}
