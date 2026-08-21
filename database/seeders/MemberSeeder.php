<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'name' => '山田 太郎',
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => '佐藤 花子',
                'notes' => '平日のみ対応',
                'is_active' => true,
            ],
            [
                'name' => '鈴木 一郎',
                'notes' => '午前中の担当を希望',
                'is_active' => true,
            ],
            [
                'name' => '高橋 美咲',
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => '確認用 新人メンバー',
                'notes' => '担当可能な掃除が1種類だけの確認用',
                'is_active' => true,
            ],
            [
                'name' => '確認用 無効メンバー',
                'notes' => '一覧の無効表示確認用',
                'is_active' => false,
            ],
        ];

        foreach ($members as $member) {
            Member::query()->updateOrCreate(
                ['name' => $member['name']],
                $member,
            );
        }
    }
}
