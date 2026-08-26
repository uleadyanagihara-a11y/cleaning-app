<?php

namespace Tests\Feature;

use App\Models\CleaningRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CleaningRoleIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_cleaning_role_list(): void
    {
        $this->get(route('cleaning-items.index'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_users_cannot_view_the_cleaning_role_list(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('cleaning-items.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_can_view_cleaning_roles_with_descriptions_and_status(): void
    {
        $user = User::factory()->create();
        CleaningRole::create([
            'name' => 'トイレ',
            'description' => '便器、床、手洗い場の清掃',
            'required_member_count' => 2,
            'is_active' => true,
        ]);
        CleaningRole::create([
            'name' => '備品補充',
            'description' => null,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('cleaning-items.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CleaningItems/Index')
                ->has('cleaningRoles', 2)
                ->where('cleaningRoles.0.name', 'トイレ')
                ->where(
                    'cleaningRoles.0.description',
                    '便器、床、手洗い場の清掃',
                )
                ->where('cleaningRoles.0.required_member_count', 2)
                ->where('cleaningRoles.0.is_active', true)
                ->where('cleaningRoles.1.name', '備品補充')
                ->where('cleaningRoles.1.description', null)
                ->where('cleaningRoles.1.required_member_count', 1)
                ->where('cleaningRoles.1.is_active', false));
    }

    public function test_active_cleaning_roles_are_listed_before_inactive_roles(): void
    {
        $user = User::factory()->create();
        CleaningRole::create(['name' => 'A 無効', 'is_active' => false]);
        CleaningRole::create(['name' => 'B 有効', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('cleaning-items.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cleaningRoles.0.name', 'B 有効')
                ->where('cleaningRoles.1.name', 'A 無効'));
    }
}
