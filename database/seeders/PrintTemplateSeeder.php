<?php

namespace Database\Seeders;

use App\Models\PrintTemplate;
use Illuminate\Database\Seeder;

class PrintTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['intake', 'transfer', 'borrow', 'disposal', 'consumable_intake', 'consumable_usage', 'consumable_inventory'];

        foreach ($modules as $module) {
            if (!PrintTemplate::where('module', $module)->exists()) {
                PrintTemplate::createDefault($module);
            }
        }
    }
}
