<?php

namespace Database\Seeders;

use App\Models\DepartmentCode;
use Illuminate\Database\Seeder;

class ConsumableDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'BGWJ', 'name' => '办公文具'],
            ['code' => 'ITPJ', 'name' => 'IT配件'],
            ['code' => 'QJYP', 'name' => '清洁用品'],
            ['code' => 'BGHC', 'name' => '办公耗材'],
            ['code' => 'LBYP', 'name' => '劳保用品'],
            ['code' => 'QT', 'name' => '其他'],
        ];

        $units = [
            ['code' => 'GE', 'name' => '个'],
            ['code' => 'ZHI', 'name' => '支'],
            ['code' => 'BAO', 'name' => '包'],
            ['code' => 'XIANG', 'name' => '箱'],
            ['code' => 'PING', 'name' => '瓶'],
            ['code' => 'JUAN', 'name' => '卷'],
            ['code' => 'TAO', 'name' => '套'],
            ['code' => 'TAI', 'name' => '台'],
            ['code' => 'JIAN', 'name' => '件'],
        ];

        $suppliers = [
            ['code' => 'JD', 'name' => '京东'],
            ['code' => 'TB', 'name' => '淘宝'],
            ['code' => 'WD', 'name' => '文达办公'],
            ['code' => 'QTGYS', 'name' => '其他供应商'],
        ];

        foreach ($categories as $item) {
            DepartmentCode::updateOrCreate(
                ['type' => 'hc_category', 'code' => $item['code']],
                ['name' => $item['name']]
            );
        }

        foreach ($units as $item) {
            DepartmentCode::updateOrCreate(
                ['type' => 'hc_unit', 'code' => $item['code']],
                ['name' => $item['name']]
            );
        }

        foreach ($suppliers as $item) {
            DepartmentCode::updateOrCreate(
                ['type' => 'supplier', 'code' => $item['code']],
                ['name' => $item['name']]
            );
        }
    }
}
