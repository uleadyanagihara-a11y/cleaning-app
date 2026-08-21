<?php

namespace Database\Seeders;

use App\Models\CleaningRole;
use Illuminate\Database\Seeder;

class CleaningRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => '玄関',
                'description' => '掃き掃除と靴箱周辺の整理',
                'is_active' => true,
            ],
            [
                'name' => 'トイレ',
                'description' => '便器、床、手洗い場の清掃',
                'is_active' => true,
            ],
            [
                'name' => '洗面所',
                'description' => '洗面台と鏡の清掃',
                'is_active' => true,
            ],
            [
                'name' => '廊下・階段',
                'description' => '掃き掃除と拭き掃除',
                'is_active' => true,
            ],
            [
                'name' => 'ごみ回収',
                'description' => 'ごみ箱の回収と袋の交換',
                'is_active' => true,
            ],
            [
                'name' => '備品補充',
                'description' => '無効データの表示確認用',
                'is_active' => false,
            ],
        ];

        foreach ($roles as $role) {
            CleaningRole::query()->updateOrCreate(
                ['name' => $role['name']],
                $role,
            );
        }
    }
}
