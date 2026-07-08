<?php

namespace Database\Seeders;

use App\Models\PrintTemplate;
use Illuminate\Database\Seeder;

class PrintTemplateSeeder extends Seeder
{
    public function run(): void
    {
        PrintTemplate::updateOrCreate(
            ['module' => 'intake'],
            [
                'name' => '资产入库单打印模板',
                'orientation' => 'landscape',
                'page_size' => 'A4',
                'config' => PrintTemplate::defaultConfig('intake'),
                'is_active' => true,
            ]
        );
    }
}
