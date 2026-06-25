<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = DB::select("SHOW INDEX FROM assets WHERE Column_name = 'ip' AND Non_unique = 0");
        foreach ($indexes as $idx) {
            $name = $idx->Key_name;
            Schema::table('assets', function (Blueprint $table) use ($name) {
                $table->dropUnique($name);
            });
        }
    }

    public function down(): void {}
};
