<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * 数据库结构漂移检测。
 *
 * 原理：在一个临时空库里跑全部 migrations，得到"代码推导结构"，
 * 再与当前真实数据库逐列、逐索引、逐表对比，找出手动改库却没写 migration 的地方。
 * 全程不碰真实数据，检测完自动删临时库。
 */
class DeployDiff extends Command
{
    protected $signature = 'deploy:diff';

    protected $description = '检测当前数据库结构与 migrations 推导结构的差异（结构漂移检测）';

    private string $conn = 'mysql';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   数据库结构漂移检测 (deploy:diff)        ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        try {
            DB::connection($this->conn)->getPdo();
        } catch (\Exception $e) {
            $this->error("数据库连接失败: {$e->getMessage()}");
            return 1;
        }

        $origDb = config("database.connections.{$this->conn}.database");
        $tmpDb = 'zcgl_schema_diff_tmp';

        $this->info("实际数据库: {$origDb}");
        $this->info('▸ 建立临时库并执行全部迁移以推导结构...');

        // 1. 建临时库
        DB::statement("DROP DATABASE IF EXISTS `{$tmpDb}`");
        DB::statement("CREATE DATABASE `{$tmpDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. 切到临时库跑迁移
        $this->switchDatabase($tmpDb);
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            $this->switchDatabase($origDb);
            DB::statement("DROP DATABASE IF EXISTS `{$tmpDb}`");
            $this->error("在临时库执行迁移失败: {$e->getMessage()}");
            return 1;
        }

        // 3. 切回真实库做跨库 information_schema 对比
        $this->switchDatabase($origDb);

        $colDiff = $this->compareColumns($origDb, $tmpDb);
        $idxDiff = $this->compareIndexes($origDb, $tmpDb);
        $tblDiff = $this->compareTables($origDb, $tmpDb);

        // 4. 清理临时库
        DB::statement("DROP DATABASE IF EXISTS `{$tmpDb}`");

        $total = count($colDiff) + count($idxDiff) + count($tblDiff);
        $this->info('✓ 检测完成，临时库已清理。');
        $this->info('');

        if ($total === 0) {
            $this->info('════════════════════════════════════════');
            $this->info('  ✓ 实际数据库结构与 migrations 完全一致，无漂移。');
            $this->info('════════════════════════════════════════');
            return 0;
        }

        $this->warn("发现 {$total} 处差异，说明数据库被手动修改且未通过 migration 记录：");
        $this->info('');

        $this->printSection('表清单差异', $tblDiff);
        $this->printSection('列级差异', $colDiff);
        $this->printSection('索引差异', $idxDiff);

        $this->info('');
        $this->warn('修复建议：针对以上差异新建 migration（php artisan make:migration），');
        $this->warn('不要直接手动改表结构。已在已有数据上验证后再部署到内网。');

        return 0;
    }

    private function switchDatabase(string $db): void
    {
        config(["database.connections.{$this->conn}.database" => $db]);
        DB::purge($this->conn);
    }

    /** 列级差异：字段名/类型/可空/默认值/extra */
    private function compareColumns(string $actual, string $mig): array
    {
        $rows = DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, TABLE_SCHEMA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA IN (?, ?) AND TABLE_NAME <> 'migrations'
             ORDER BY TABLE_NAME, COLUMN_NAME",
            [$actual, $mig]
        );

        $map = [];
        foreach ($rows as $r) {
            $key = $r->TABLE_NAME . '.' . $r->COLUMN_NAME;
            $map[$r->TABLE_SCHEMA][$key] = [
                'table' => $r->TABLE_NAME,
                'column' => $r->COLUMN_NAME,
                'type' => $r->COLUMN_TYPE,
                'null' => $r->IS_NULLABLE,
                'default' => $r->COLUMN_DEFAULT,
                'extra' => $r->EXTRA,
            ];
        }

        $actualMap = $map[$actual] ?? [];
        $migMap = $map[$mig] ?? [];
        $diffs = [];

        foreach ($actualMap as $key => $a) {
            if (!isset($migMap[$key])) {
                $diffs[] = ['kind' => '实际库独有(migration缺失)', 'table' => $a['table'], 'column' => $a['column'], 'actual' => "{$a['type']} null={$a['null']}", 'mig' => '—'];
                continue;
            }
            $b = $migMap[$key];
            if ($a['type'] !== $b['type'] || $a['null'] !== $b['null'] || (string)$a['default'] !== (string)$b['default'] || $a['extra'] !== $b['extra']) {
                $diffs[] = ['kind' => '属性不同', 'table' => $a['table'], 'column' => $a['column'], 'actual' => "{$a['type']} null={$a['null']} default={$a['default']} extra={$a['extra']}", 'mig' => "{$b['type']} null={$b['null']} default={$b['default']} extra={$b['extra']}"];
            }
        }
        foreach ($migMap as $key => $b) {
            if (!isset($actualMap[$key])) {
                $diffs[] = ['kind' => 'migration独有(实际库缺失)', 'table' => $b['table'], 'column' => $b['column'], 'actual' => '—', 'mig' => "{$b['type']} null={$b['null']}"];
            }
        }

        usort($diffs, fn($x, $y) => strcmp($x['table'] . $x['column'], $y['table'] . $y['column']));
        return $diffs;
    }

    /** 索引差异：索引名/列/唯一性 */
    private function compareIndexes(string $actual, string $mig): array
    {
        $rows = DB::select(
            "SELECT TABLE_SCHEMA, TABLE_NAME, INDEX_NAME, NON_UNIQUE,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS cols
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA IN (?, ?)
             GROUP BY TABLE_SCHEMA, TABLE_NAME, INDEX_NAME, NON_UNIQUE
             ORDER BY TABLE_NAME, INDEX_NAME",
            [$actual, $mig]
        );

        $map = [];
        foreach ($rows as $r) {
            $key = $r->TABLE_NAME . '.' . $r->INDEX_NAME;
            $map[$r->TABLE_SCHEMA][$key] = [
                'table' => $r->TABLE_NAME,
                'index' => $r->INDEX_NAME,
                'cols' => $r->cols,
                'unique' => $r->NON_UNIQUE == 0 ? '唯一' : '普通',
            ];
        }

        $actualMap = $map[$actual] ?? [];
        $migMap = $map[$mig] ?? [];
        $diffs = [];

        foreach ($actualMap as $key => $a) {
            if (!isset($migMap[$key])) {
                // 同列同唯一性的索引仅名字不同，不算实质差异
                $equiv = $this->findEquivalent($migMap, $a);
                if ($equiv) {
                    $diffs[] = ['kind' => '仅索引名不同(功能等价)', 'table' => $a['table'], 'column' => $a['index'] . "({$a['cols']})", 'actual' => $a['index'] . " [{$a['unique']}]", 'mig' => $equiv['index'] . " [{$equiv['unique']}]"];
                } else {
                    $diffs[] = ['kind' => '实际库独有(migration缺失)', 'table' => $a['table'], 'column' => $a['index'] . "({$a['cols']})", 'actual' => "{$a['index']} [{$a['unique']} {$a['cols']}]", 'mig' => '—'];
                }
                continue;
            }
            $b = $migMap[$key];
            if ($a['cols'] !== $b['cols'] || $a['unique'] !== $b['unique']) {
                $diffs[] = ['kind' => '索引定义不同', 'table' => $a['table'], 'column' => $a['index'], 'actual' => "[{$a['unique']} {$a['cols']}]", 'mig' => "[{$b['unique']} {$b['cols']}]"];
            }
        }
        foreach ($migMap as $key => $b) {
            if (!isset($actualMap[$key]) && !$this->findEquivalent($actualMap, $b)) {
                $diffs[] = ['kind' => 'migration独有(实际库缺失)', 'table' => $b['table'], 'column' => $b['index'] . "({$b['cols']})", 'actual' => '—', 'mig' => "{$b['index']} [{$b['unique']} {$b['cols']}]"];
            }
        }

        usort($diffs, fn($x, $y) => strcmp($x['table'] . $x['column'], $y['table'] . $y['column']));
        return $diffs;
    }

    /** 找同表、同列、同唯一性但名字不同的等价索引 */
    private function findEquivalent(array $map, array $target): ?array
    {
        foreach ($map as $m) {
            if ($m['table'] === $target['table'] && $m['cols'] === $target['cols'] && $m['unique'] === $target['unique']) {
                return $m;
            }
        }
        return null;
    }

    /** 表清单差异 */
    private function compareTables(string $actual, string $mig): array
    {
        $actualTables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $actual)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->pluck('TABLE_NAME')
            ->all();
        $migTables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $mig)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->pluck('TABLE_NAME')
            ->all();

        $actualSet = array_diff($actualTables, ['migrations']);
        $migSet = array_diff($migTables, ['migrations']);
        $diffs = [];
        foreach (array_diff($actualSet, $migSet) as $t) {
            $diffs[] = ['table' => $t, 'kind' => '实际库独有(migration未建)', 'actual' => '存在', 'mig' => '—'];
        }
        foreach (array_diff($migSet, $actualSet) as $t) {
            $diffs[] = ['table' => $t, 'kind' => 'migration独有(实际库缺失)', 'actual' => '—', 'mig' => '存在'];
        }
        usort($diffs, fn($x, $y) => strcmp($x['table'], $y['table']));
        return $diffs;
    }

    private function printSection(string $title, array $diffs): void
    {
        if (empty($diffs)) {
            return;
        }
        $this->warn("── {$title}（" . count($diffs) . "）──");
        foreach ($diffs as $d) {
            $loc = $d['table'] . (isset($d['column']) ? '.' . $d['column'] : '');
            $this->line(sprintf("  [%s] %s", $d['kind'], $loc));
            $this->line(sprintf("        实际库: %s", $d['actual']));
            $this->line(sprintf("        迁移库: %s", $d['mig']));
        }
        $this->info('');
    }
}
