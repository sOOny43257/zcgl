<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeployMigrate extends Command
{
    protected $signature = 'deploy:migrate
        {--force : 跳过确认提示，适用于自动化部署}
        {--no-backup : 跳过数据库备份（不推荐）}
        {--pretend : 仅显示待执行的迁移，不实际执行}';

    protected $description = '安全部署迁移：自动备份数据库，然后执行待运行的迁移';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     资产管理系统 - 安全数据库迁移工具       ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // 1. 检测数据库连接
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->info("✓ 数据库连接成功: {$dbName}");
        } catch (\Exception $e) {
            $this->error("✗ 数据库连接失败: {$e->getMessage()}");
            $this->error('  请先配置 .env 文件中的数据库连接信息');
            return 1;
        }

        // 2. 检查 migrations 表是否存在
        $hasMigrationsTable = $this->hasMigrationsTable();
        $appliedMigrations = $hasMigrationsTable ? $this->getAppliedMigrations() : [];

        // 3. 扫描待执行的迁移
        $pendingMigrations = $this->getPendingMigrations($appliedMigrations);

        $this->info('');
        $this->info("已执行迁移数: " . count($appliedMigrations));
        $this->info("待执行迁移数: " . count($pendingMigrations));
        $this->info('');

        if (empty($pendingMigrations)) {
            $this->info('✓ 数据库结构已是最新，无需迁移。');
            $this->updateVersionFile();
            return 0;
        }

        // 4. 显示待执行迁移列表
        $this->info('待执行的迁移:');
        $this->info('────────────────────────────────────────');
        foreach ($pendingMigrations as $i => $migration) {
            $this->info("  " . ($i + 1) . ". {$migration}");
        }
        $this->info('────────────────────────────────────────');
        $this->info('');

        // 5. Pretend 模式
        if ($this->option('pretend')) {
            $this->warn('--pretend 模式：仅展示，不执行任何操作。');
            return 0;
        }

        // 6. 确认执行
        if (!$this->option('force')) {
            if (!$this->confirm('确认执行以上迁移？此操作不可轻易撤销。', true)) {
                $this->warn('已取消。');
                return 0;
            }
        }

        // 7. 备份数据库
        if (!$this->option('no-backup')) {
            $this->backupDatabase($dbName);
        }

        // 8. 执行迁移
        $this->info('');
        $this->info('开始执行迁移...');
        $this->info('');

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            $this->info($output);
        } catch (\Exception $e) {
            $this->error("✗ 迁移执行失败: {$e->getMessage()}");
            $this->error('');
            $this->error('数据库备份已保存，如需恢复请手动操作。');
            return 1;
        }

        // 9. 更新版本文件
        $this->updateVersionFile();

        $this->info('');
        $this->info('════════════════════════════════════════');
        $this->info('  ✓ 迁移完成！数据库结构已同步。');
        $this->info('════════════════════════════════════════');
        $this->info('');

        return 0;
    }

    /**
     * 检查 migrations 表是否存在
     */
    private function hasMigrationsTable(): bool
    {
        try {
            return count(DB::select("SHOW TABLES LIKE 'migrations'")) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取已执行的迁移列表
     */
    private function getAppliedMigrations(): array
    {
        try {
            return DB::table('migrations')->pluck('migration')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取待执行的迁移文件
     */
    private function getPendingMigrations(array $applied): array
    {
        $migrationPath = database_path('migrations');
        $files = glob("{$migrationPath}/*.php");

        $pending = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $applied)) {
                $pending[] = $name;
            }
        }

        sort($pending);
        return $pending;
    }

    /**
     * 备份当前数据库
     */
    private function backupDatabase(string $dbName): void
    {
        $this->info('正在备份数据库...');

        $backupDir = storage_path('app/backups/deploy');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = sprintf(
            '%s_%s.sql',
            $dbName,
            date('Y-m-d_His')
        );
        $filepath = $backupDir . '/' . $filename;

        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $user = config('database.connections.mysql.username', 'root');
            $pass = config('database.connections.mysql.password', '');

            $cmd = sprintf(
                'mysqldump -h%s -P%s -u%s %s --single-transaction --routines --triggers > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                $pass !== '' ? '-p' . escapeshellarg($pass) : '',
                escapeshellarg($filepath)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($filepath)) {
                $size = number_format(filesize($filepath) / 1024, 1);
                $this->info("✓ 备份已保存: {$filepath} ({$size} KB)");
            } else {
                $this->warn("⚠ 备份命令执行异常，返回码: {$returnCode}");
                $this->warn('  迁移将继续执行，请确保您有其他备份方式。');
            }
        } catch (\Exception $e) {
            $this->warn("⚠ 备份失败: {$e->getMessage()}");
            $this->warn('  迁移将继续执行，请确保您有其他备份方式。');
        }
    }

    /**
     * 更新版本文件中的迁移记录
     */
    private function updateVersionFile(): void
    {
        $versionFile = storage_path('app/version.json');
        $data = file_exists($versionFile)
            ? json_decode(file_get_contents($versionFile), true) ?? []
            : [];

        $data['last_deploy'] = date('Y-m-d H:i:s');
        $data['last_deploy_migrations'] = DB::table('migrations')->count();

        file_put_contents(
            $versionFile,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
