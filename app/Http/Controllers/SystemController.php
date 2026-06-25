<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    public function index()
    {
        $backups = $this->listBackups();
        $dbSize = $this->getDbSize();
        $mysqldumpPath = $this->findBinary('mysqldump');
        return view('system.index', compact('backups', 'dbSize', 'mysqldumpPath'));
    }

    // 初始化数据库
    public function init()
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
            return back()->with('success', '数据库初始化完成 — 所有表已重建，默认数据已导入');
        } catch (\Exception $e) {
            return back()->with('error', '初始化失败: ' . $e->getMessage());
        }
    }

    // 备份数据库
    public function backup()
    {
        try {
            $db = config('database.connections.mysql');
            $mysqldump = $this->findBinary('mysqldump');

            $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
            $path = storage_path('backups');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $filepath = $path . '/' . $filename;

            // 用临时配置文件传密码，避免命令行密码泄漏警告污染 SQL 文件
            $cnfFile = $this->createTempCnf($db);

            $host = $db['host'] ?? '127.0.0.1';
            $port = $db['port'] ?? '3306';

            $command = sprintf(
                '%s --defaults-extra-file=%s -h %s -P %s %s > %s 2>/dev/null',
                escapeshellarg($mysqldump),
                escapeshellarg($cnfFile),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($db['database']),
                escapeshellarg($filepath)
            );

            $this->execCommand($command);
            @unlink($cnfFile);

            return back()->with('success', "备份完成：{$filename}");
        } catch (\Exception $e) {
            return back()->with('error', '备份失败: ' . $e->getMessage());
        }
    }

    // 还原数据库
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|string',
        ]);

        $filepath = storage_path('backups/' . basename($request->backup_file));

        if (!File::exists($filepath)) {
            return back()->with('error', '备份文件不存在');
        }

        try {
            $db = config('database.connections.mysql');
            $mysql = $this->findBinary('mysql');

            $cnfFile = $this->createTempCnf($db);

            $host = $db['host'] ?? '127.0.0.1';
            $port = $db['port'] ?? '3306';

            $command = sprintf(
                '%s --defaults-extra-file=%s -h %s -P %s %s < %s 2>&1',
                escapeshellarg($mysql),
                escapeshellarg($cnfFile),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($db['database']),
                escapeshellarg($filepath)
            );

            $this->execCommand($command);
            @unlink($cnfFile);

            return back()->with('success', '数据还原完成');
        } catch (\Exception $e) {
            return back()->with('error', '还原失败: ' . $e->getMessage());
        }
    }

    // 下载备份文件
    public function downloadBackup($filename)
    {
        $filepath = storage_path('backups/' . basename($filename));
        if (!File::exists($filepath)) abort(404);
        return response()->download($filepath);
    }

    // 删除备份
    public function deleteBackup(Request $request)
    {
        $filepath = storage_path('backups/' . basename($request->file));
        if (File::exists($filepath)) File::delete($filepath);
        return back()->with('success', '备份已删除');
    }

    // 访问日志
    public function logs(Request $request)
    {
        $query = AccessLog::orderBy('created_at', 'desc');

        if ($request->filled('user')) {
            $query->where('user_name', 'like', "%{$request->user}%");
        }
        if ($request->filled('ip')) {
            $query->where('ip', 'like', "%{$request->ip}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('system.logs', compact('logs'));
    }

    // 清空日志
    public function clearLogs()
    {
        AccessLog::truncate();
        return back()->with('success', '访问日志已清空');
    }

    /**
     * 在 storage/app 下创建临时 MySQL 配置文件（Apache 可写），返回路径。
     */
    private function createTempCnf(array $db): string
    {
        $tmpDir = storage_path('app');
        if (!File::exists($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }

        $cnfFile = $tmpDir . '/.mysql_cnf_' . bin2hex(random_bytes(8));
        $cnfContent = "[client]\nuser={$db['username']}\npassword={$db['password']}\n";
        if (!empty($db['unix_socket'])) {
            $cnfContent .= "socket={$db['unix_socket']}\n";
        }
        file_put_contents($cnfFile, $cnfContent);
        chmod($cnfFile, 0600);
        return $cnfFile;
    }

    /**
     * 自动查找 mysql / mysqldump 命令的完整路径。
     * 优先使用 .env 中配置的 MYSQL_BIN_DIR，否则自动探测常见安装位置。
     */
    private function findBinary(string $name): string
    {
        // 1) .env 显式指定目录
        $binDir = env('MYSQL_BIN_DIR');
        if ($binDir) {
            $full = rtrim($binDir, '/') . '/' . $name;
            if (is_executable($full)) {
                return $full;
            }
        }

        // 2) 系统 PATH（exec 可用时）
        if (function_exists('exec')) {
            $output = [];
            exec("which {$name} 2>/dev/null", $output, $code);
            if ($code === 0 && !empty($output[0]) && is_executable(trim($output[0]))) {
                return trim($output[0]);
            }
        }

        // 3) 常见安装路径
        $candidates = [
            // XAMPP (macOS / Linux)
            '/Applications/XAMPP/xamppfiles/bin/' . $name,
            '/opt/lampp/bin/' . $name,
            // Homebrew
            '/opt/homebrew/bin/' . $name,
            '/usr/local/bin/' . $name,
            // Linux 标准
            '/usr/bin/' . $name,
            '/usr/sbin/' . $name,
            // MariaDB (某些发行版)
            '/usr/local/mariadb/bin/' . $name,
            '/usr/local/mysql/bin/' . $name,
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // 4) 兜底：直接用命令名
        return $name;
    }

    private function listBackups(): array
    {
        $path = storage_path('backups');
        if (!File::exists($path)) return [];

        return collect(File::files($path))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sql'))
            ->map(fn($f) => [
                'name' => $f->getFilename(),
                'size' => $this->formatBytes($f->getSize()),
                'date' => date('Y-m-d H:i:s', $f->getMTime()),
            ])
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    private function getDbSize(): string
    {
        $db = config('database.connections.mysql.database');
        $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?", [$db]);
        return ($result[0]->size ?? 0) . ' MB';
    }

    private function formatBytes($bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    private function execCommand(string $command): void
    {
        $code = 0;
        if (function_exists('exec')) {
            exec($command, $output, $code);
        } elseif (function_exists('shell_exec')) {
            shell_exec($command);
        } else {
            throw new \Exception('exec 和 shell_exec 均被禁用，请检查 PHP 配置（disable_functions）');
        }
        if ($code !== 0) {
            throw new \Exception("命令执行失败 (exit code: {$code})。请确认 MySQL 客户端工具已安装，并在 .env 中配置 MYSQL_BIN_DIR");
        }
    }
}
