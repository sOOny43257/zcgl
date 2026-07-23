<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeployStatus extends Command
{
    protected $signature = 'deploy:status';

    protected $description = '查看数据库部署状态：已执行迁移数、待执行迁移数、版本信息';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     资产管理系统 - 部署状态               ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // 数据库连接
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->info("数据库: {$dbName}");
            $this->info("连接:   " . config('database.connections.mysql.host') . ':' . config('database.connections.mysql.port'));
        } catch (\Exception $e) {
            $this->error("数据库连接失败: {$e->getMessage()}");
            return 1;
        }

        // 版本文件
        $versionFile = storage_path('app/version.json');
        if (file_exists($versionFile)) {
            $data = json_decode(file_get_contents($versionFile), true);
            $this->info("应用版本: " . ($data['version'] ?? '未知'));
            if (isset($data['last_deploy'])) {
                $this->info("上次部署: " . $data['last_deploy']);
            }
        } else {
            $this->warn("版本文件不存在（首次部署?）");
        }

        $this->info('');

        // 迁移状态
        $hasTable = DB::select("SHOW TABLES LIKE 'migrations'");
        if ($hasTable) {
            $applied = DB::table('migrations')->pluck('migration')->toArray();
            $appliedCount = count($applied);

            $migrationPath = database_path('migrations');
            $allFiles = glob("{$migrationPath}/*.php");
            $totalCount = count($allFiles);
            $pendingCount = max(0, $totalCount - $appliedCount);

            $this->info("已执行迁移: {$appliedCount}");
            $this->info("待执行迁移: {$pendingCount}");

            if ($pendingCount > 0) {
                $this->warn("有 {$pendingCount} 个迁移待执行，请运行 php artisan deploy:migrate");
                $this->info('');
                $this->info('待执行迁移:');
                foreach ($allFiles as $file) {
                    $name = basename($file, '.php');
                    if (!in_array($name, $applied)) {
                        $this->info("  · {$name}");
                    }
                }
            } else {
                $this->info('✓ 所有迁移已执行，数据库结构是最新的。');
            }
        } else {
            $totalCount = count(glob(database_path('migrations') . '/*.php'));
            $this->warn("migrations 表不存在，需要初始化数据库。");
            $this->info("待执行迁移: {$totalCount}（全部）");
            $this->info('请运行 php artisan deploy:migrate');
        }

        $this->info('');

        // 表统计
        $this->info('数据表统计:');
        $this->info('────────────────────────────────────────');
        $tables = DB::select("SHOW TABLES");
        $key = 'Tables_in_' . DB::connection()->getDatabaseName();
        foreach ($tables as $table) {
            $tableName = $table->$key ?? current((array) $table);
            if ($tableName === 'migrations') continue;
            try {
                $count = DB::table($tableName)->count();
                $this->info(sprintf("  %-30s %6d 条记录", $tableName, $count));
            } catch (\Exception $e) {
                $this->info(sprintf("  %-30s (无法计数)", $tableName));
            }
        }
        $this->info('────────────────────────────────────────');
        $this->info('');

        return 0;
    }
}
