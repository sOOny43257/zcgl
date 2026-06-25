<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UpdateController extends Controller
{
    public function index()
    {
        $data = json_decode(file_get_contents(storage_path('app/version.json')), true);
        return view('updates.index', [
            'version' => $data['version'] ?? 'v1.0.0',
            'history' => $data['history'] ?? [],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'update_file' => 'required|file|mimes:gz,tgz,tar|extensions:gz,tar.gz,tgz',
        ]);

        $file = $request->file('update_file');
        $tmpDir = storage_path('app/update_tmp');
        $this->cleanDir($tmpDir);
        mkdir($tmpDir, 0755, true);

        // 解压（优先用 PHP PharData，失败则回退到 exec tar）
        $archive = $file->getRealPath();
        try {
            $phar = new \PharData($archive);
            $phar->extractTo($tmpDir, null, true);
        } catch (\Exception $e) {
            // PharData 失败，尝试 exec tar
            exec('tar -xzf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($tmpDir) . ' 2>&1', $output, $code);
            if ($code !== 0) {
                $err = function_exists('exec') ? implode("\n", $output) : 'exec 函数被禁用，且 PharData 不可用';
                $this->cleanDir($tmpDir);
                return back()->with('error', '解压失败：' . $err);
            }
        }

        // 读取 manifest
        $manifestFile = $tmpDir . '/manifest.json';
        if (!file_exists($manifestFile)) {
            $this->cleanDir($tmpDir);
            return back()->with('error', '缺少 manifest.json');
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (!$manifest || empty($manifest['version'])) {
            $this->cleanDir($tmpDir);
            return back()->with('error', 'manifest.json 无效');
        }

        $newVersion = $manifest['version'];
        $backupDir = storage_path('app/backups/versions/' . $newVersion);
        $this->cleanDir($backupDir);
        mkdir($backupDir, 0755, true);

        // 记录新迁移数量（之后回滚用）
        $migrationsCount = 0;

        // 替换文件前先备份被覆盖的文件
        $filesDir = $tmpDir . '/files';
        if (is_dir($filesDir)) {
            $this->backupFiles($filesDir, $backupDir);
            try {
                $this->copyFiles($filesDir, base_path());
            } catch (\Exception $e) {
                $this->cleanDir($tmpDir);
                return back()->with('error', '文件写入失败，请检查权限：' . $e->getMessage());
            }
        }

        // 运行迁移
        $migrationsDir = $tmpDir . '/migrations';
        if (is_dir($migrationsDir)) {
            $migrationFiles = glob($migrationsDir . '/*.php');
            $migrationsCount = count($migrationFiles);
            foreach ($migrationFiles as $mf) {
                copy($mf, database_path('migrations/' . basename($mf)));
            }
            Artisan::call('migrate', ['--force' => true]);
        }

        $this->cleanDir($tmpDir);

        // 更新版本记录
        $versionFile = storage_path('app/version.json');
        $data = json_decode(file_get_contents($versionFile), true);
        $data['version'] = $newVersion;
        array_unshift($data['history'], [
            'version' => $newVersion,
            'date' => date('Y-m-d'),
            'desc' => $manifest['desc'] ?? '系统更新',
            'migrations_count' => $migrationsCount,
            'backup' => 'storage/app/backups/versions/' . $newVersion,
        ]);
        file_put_contents($versionFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        return redirect()->route('updates.index')->with('success', "更新成功！当前版本：{$newVersion}");
    }

    public function rollback(Request $request)
    {
        $rollVer = $request->input('version');
        $data = json_decode(file_get_contents(storage_path('app/version.json')), true);

        // 找到要回滚到的版本
        $rollIndex = null;
        foreach ($data['history'] as $i => $h) {
            if ($h['version'] === $rollVer) { $rollIndex = $i; break; }
        }
        if ($rollIndex === null) {
            return back()->with('error', '未找到该版本');
        }

        // 回滚该版本之后的所有更新（从最新到目标版本之后）
        $toRollback = array_slice($data['history'], 0, $rollIndex);
        $skipped = [];
        foreach ($toRollback as $h) {
            $backupDir = storage_path('app/backups/versions/' . $h['version']);
            if (is_dir($backupDir)) {
                $this->copyFiles($backupDir, base_path());
            } else {
                $skipped[] = $h['version'];
            }
            // 回滚迁移
            $mc = $h['migrations_count'] ?? 0;
            for ($i = 0; $i < $mc; $i++) {
                try { Artisan::call('migrate:rollback', ['--force' => true]); } catch (\Exception $e) {}
            }
        }

        // 更新版本号为目标版本
        $data['version'] = $rollVer;
        $data['history'] = array_slice($data['history'], $rollIndex);
        file_put_contents(storage_path('app/version.json'), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        $msg = "已回滚至 {$rollVer}";
        if (!empty($skipped)) {
            $msg .= "（注意：" . implode(', ', $skipped) . " 无备份文件，版本号已回退但文件需手动恢复）";
        }
        return redirect()->route('updates.index')->with('success', $msg);
    }

    private function backupFiles(string $updateDir, string $backupDir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($updateDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($files as $f) {
            $relPath = str_replace($updateDir . '/', '', $f->getPathname());
            $source = base_path($relPath);
            if (file_exists($source)) {
                $dest = $backupDir . '/' . $relPath;
                $destParent = dirname($dest);
                if (!is_dir($destParent)) mkdir($destParent, 0755, true);
                copy($source, $dest);
            }
        }
    }

    private function copyFiles(string $from, string $to): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($files as $f) {
            $relPath = str_replace($from . '/', '', $f->getPathname());
            $dest = $to . '/' . $relPath;
            $destParent = dirname($dest);
            if (!is_dir($destParent)) mkdir($destParent, 0755, true);
            copy($f->getPathname(), $dest);
        }
    }

    private function cleanDir(string $dir): void
    {
        if (is_dir($dir)) exec('rm -rf ' . escapeshellarg($dir));
    }
}
