<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    public function index()
    {
        if (!env('APP_KEY')) {
            \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
        }
        return view('install.step1');
    }

    public function setupDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_name' => 'required',
            'db_user' => 'required',
        ]);

        $host = $request->db_host;
        $port = $request->db_port;
        $database = $request->db_name;
        $username = $request->db_user;
        $password = $request->db_pass ?? '';

        // 测试连接
        try {
            config(['database.connections._install' => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]]);

            DB::connection('_install')->getPdo();
        } catch (\Exception $e) {
            return back()->with('error', '数据库连接失败：' . $e->getMessage())->withInput();
        }

        // 写入 .env
        $this->updateEnv([
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        // 重新连接默认数据库
        config(['database.connections.mysql.host' => $host]);
        config(['database.connections.mysql.port' => $port]);
        config(['database.connections.mysql.database' => $database]);
        config(['database.connections.mysql.username' => $username]);
        config(['database.connections.mysql.password' => $password]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        // 运行迁移
        try {
            Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]);
            $output = Artisan::output();
        } catch (\Exception $e) {
            return back()->with('error', '数据库初始化失败：' . $e->getMessage())->withInput();
        }

        return view('install.step2', ['migrateOutput' => $output]);
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'username' => 'required|min:3|max:50',
            'name' => 'required|max:100',
            'password' => 'required|min:6|confirmed',
        ]);

        try {
            User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->username . '@local.local',
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'department' => '信息中心',
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', '创建管理员失败：' . $e->getMessage())->withInput();
        }

        // 写入安装标记
        file_put_contents(storage_path('app/installed'), date('Y-m-d H:i:s'));

        return redirect('/login')->with('success', '系统安装完成！请使用刚才创建的管理员账号登录。');
    }

    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        $content = file_exists($envPath) ? file_get_contents($envPath) : '';

        foreach ($data as $key => $value) {
            if (str_contains($content, $key . '=')) {
                $content = preg_replace(
                    '/' . $key . '=.*/',
                    $key . '=' . $value,
                    $content
                );
            } else {
                $content .= "\n" . $key . '=' . $value;
            }
        }

        file_put_contents($envPath, $content);
    }
}
