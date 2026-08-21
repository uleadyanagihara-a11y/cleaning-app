<?php

namespace Database\Seeders;

use App\Models\CleaningRole;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberCleaningRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $availableRolesByMember = [
            '山田 太郎' => ['玄関', '廊下・階段', 'ごみ回収'],
            '佐藤 花子' => ['トイレ', '洗面所'],
            '鈴木 一郎' => ['玄関', 'ごみ回収'],
            '高橋 美咲' => ['玄関', 'トイレ', '洗面所', '廊下・階段', 'ごみ回収'],
            '確認用 新人メンバー' => ['玄関'],
            '確認用 無効メンバー' => [],
        ];

        foreach ($availableRolesByMember as $memberName => $roleNames) {
            $member = Member::query()
                ->where('name', $memberName)
                ->firstOrFail();

            $roleIds = CleaningRole::query()
                ->whereIn('name', $roleNames)
                ->pluck('id');

            $member->availableCleaningRoles()->sync($roleIds);
        }
    }
}
