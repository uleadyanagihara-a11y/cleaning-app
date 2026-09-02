<?php

namespace Tests\Feature;

use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_member_list(): void
    {
        $this->get(route('members.index'))
            ->assertRedirect(route('login'));
    }

    public function test_users_can_view_the_member_list_without_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_members_with_roles_notes_and_status(): void
    {
        $user = User::factory()->create();
        $role = CleaningRole::create(['name' => '玄関']);
        $activeMember = Member::create([
            'name' => '山田 太郎',
            'notes' => '平日のみ対応',
            'is_active' => true,
        ]);
        $activeMember->availableCleaningRoles()->attach($role);
        Member::create([
            'name' => '確認用 無効メンバー',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('members.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Index')
                ->where('counts.all', 2)
                ->where('counts.active', 1)
                ->where('counts.inactive', 1)
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->has('members.data', 2)
                ->where('members.data.0.name', '山田 太郎')
                ->where('members.data.0.notes', '平日のみ対応')
                ->where('members.data.0.is_active', true)
                ->has('members.data.0.available_cleaning_roles', 1)
                ->where(
                    'members.data.0.available_cleaning_roles.0.name',
                    '玄関',
                )
                ->where('members.data.1.name', '確認用 無効メンバー')
                ->where('members.data.1.is_active', false));
    }

    public function test_members_can_be_searched_and_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Member::create(['name' => '検索対象 有効', 'is_active' => true]);
        Member::create(['name' => '検索対象 無効', 'is_active' => false]);
        Member::create(['name' => '別メンバー', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('members.index', [
            'search' => '検索対象',
            'status' => 'inactive',
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Index')
                ->where('filters.search', '検索対象')
                ->where('filters.status', 'inactive')
                ->has('members.data', 1)
                ->where('members.data.0.name', '検索対象 無効')
                ->where('counts.all', 3)
                ->where('counts.active', 1)
                ->where('counts.inactive', 2));
    }

    public function test_only_active_cleaning_roles_are_available_for_registration(): void
    {
        $user = User::factory()->create();
        CleaningRole::create(['name' => '玄関', 'is_active' => true]);
        CleaningRole::create(['name' => '廊下', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('cleaningRoles', 1)
                ->where('cleaningRoles.0.name', '玄関'));
    }

    public function test_member_list_is_paginated_twenty_members_at_a_time(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 21) as $number) {
            Member::create([
                'name' => sprintf('メンバー%02d', $number),
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($user)->get(route('members.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 20)
                ->where('members.current_page', 1)
                ->where('members.last_page', 2)
                ->where('members.per_page', 20)
                ->where('members.total', 21));
    }
}
