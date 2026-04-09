<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Phòng Công Nghệ',
            'Phòng Cơ Sở Vật Chất',
            'Phòng Truyền Thông & Marketing',
            'Phòng Đầu Tư',
            'Phòng Kế Toán',
            'Phòng Hành Chính Nhân Sự',
            'Hội Đồng Hiệu Trưởng',
            'Phòng Kinh Doanh',
            'Phòng Mua Hàng',
        ];

        foreach ($names as $name) {
            Department::query()->firstOrCreate(
                ['name' => $name],
                []
            );
        }
    }
}
