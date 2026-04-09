<?php

namespace Database\Seeders;

use App\Models\Block;
use Illuminate\Database\Seeder;

class BlockSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Khối Giảng Dạy', 'code' => 'K_GD'],
            ['name' => 'Khối Văn Phòng', 'code' => 'K_VP'],
        ];

        foreach ($rows as $row) {
            Block::query()->firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name']]
            );
        }
    }
}
