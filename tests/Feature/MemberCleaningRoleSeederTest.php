<?php

namespace Tests\Feature;

use App\Models\CleaningRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberCleaningRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_table_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('cleaning_role_member', [
            'id',
            'member_id',
            'cleaning_role_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_database_seeder_creates_members_roles_and_their_availability(): void
    {
        $this->seed();

        $expectedRolesByMember = [
            '山田 太郎' => ['玄関', '廊下・階段', 'ごみ回収'],
            '佐藤 花子' => ['トイレ', '洗面所'],
            '鈴木 一郎' => ['玄関', 'ごみ回収'],
            '高橋 美咲' => ['玄関', 'トイレ', '洗面所', '廊下・階段', 'ごみ回収'],
            '確認用 新人メンバー' => ['玄関'],
            '確認用 無効メンバー' => [],
        ];

        foreach ($expectedRolesByMember as $memberName => $roleNames) {
            $actualRoleNames = Member::query()
                ->where('name', $memberName)
                ->firstOrFail()
                ->availableCleaningRoles()
                ->pluck('name')
                ->all();

            $this->assertEqualsCanonicalizing($roleNames, $actualRoleNames);
        }

        $entranceMemberNames = CleaningRole::query()
            ->where('name', '玄関')
            ->firstOrFail()
            ->availableMembers()
            ->pluck('name')
            ->all();

        $this->assertEqualsCanonicalizing([
            '山田 太郎',
            '鈴木 一郎',
            '高橋 美咲',
            '確認用 新人メンバー',
        ], $entranceMemberNames);

        $this->assertDatabaseHas('members', [
            'name' => '確認用 無効メンバー',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('cleaning_roles', [
            'name' => '備品補充',
            'is_active' => false,
        ]);
        $this->assertFalse(
            CleaningRole::query()
                ->where('name', '備品補充')
                ->firstOrFail()
                ->availableMembers()
                ->exists(),
        );
    }

    public function test_database_seeder_can_be_run_repeatedly_without_duplicates(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(6, Member::query()->count());
        $this->assertSame(6, CleaningRole::query()->count());
        $this->assertSame(13, DB::table('cleaning_role_member')->count());
    }
}
