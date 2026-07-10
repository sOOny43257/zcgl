<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 创建管理员账号
        User::create([
            'name' => '系统管理员',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'department' => '信息中心',
            'is_active' => true,
        ]);

        // 创建普通用户
        User::create([
            'name' => '张三',
            'username' => 'zhangsan',
            'email' => 'zhangsan@example.com',
            'password' => Hash::make('123456'),
            'role' => 'user',
            'department' => '财务部',
            'is_active' => true,
        ]);

        // 创建测试资产数据
        $assets = [
            ['name' => '财务部台式机-001', 'department' => '财务部', 'room' => '301', 'ip' => '192.168.1.101', 'mac' => '00:1A:2B:3C:4D:01', 'sn' => 'SN20230001', 'category' => '计算机', 'status' => '在用'],
            ['name' => '财务部台式机-002', 'department' => '财务部', 'room' => '301', 'ip' => '192.168.1.102', 'mac' => '00:1A:2B:3C:4D:02', 'sn' => 'SN20230002', 'category' => '计算机', 'status' => '在用'],
            ['name' => '财务部打印机', 'department' => '财务部', 'room' => '302', 'ip' => '192.168.1.103', 'mac' => '00:1A:2B:3C:4D:03', 'sn' => 'SN20230003', 'category' => '打印机', 'status' => '在用'],
            ['name' => '人事部笔记本-001', 'department' => '人事部', 'room' => '401', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:11', 'sn' => 'SN20230011', 'category' => '计算机', 'status' => '在用'],
            ['name' => '人事部笔记本-002', 'department' => '人事部', 'room' => '402', 'ip' => '192.168.1.202', 'mac' => '00:1A:2B:3C:4D:12', 'sn' => 'SN20230012', 'category' => '计算机', 'status' => '闲置'],
            ['name' => '信息中心服务器-001', 'department' => '信息中心', 'room' => '101', 'ip' => '192.168.1.1', 'mac' => '00:1A:2B:3C:4D:AA', 'sn' => 'SN20230021', 'category' => '服务器', 'status' => '在用'],
            ['name' => '信息中心交换机-001', 'department' => '信息中心', 'room' => '101', 'ip' => '192.168.1.254', 'mac' => '00:1A:2B:3C:4D:BB', 'sn' => 'SN20230022', 'category' => '交换机', 'status' => '在用'],
            ['name' => '信息中心路由器-001', 'department' => '信息中心', 'room' => '101', 'ip' => '192.168.1.253', 'mac' => '00:1A:2B:3C:4D:CC', 'sn' => 'SN20230023', 'category' => '路由器', 'status' => '在用'],
            ['name' => '行政部台式机-001', 'department' => '行政部', 'room' => '201', 'ip' => '192.168.1.131', 'mac' => '00:1A:2B:3C:4D:31', 'sn' => 'SN20230031', 'category' => '计算机', 'status' => '维修'],
            ['name' => '行政部显示器-001', 'department' => '行政部', 'room' => '201', 'ip' => '192.168.1.132', 'mac' => '00:1A:2B:3C:4D:32', 'sn' => 'SN20230032', 'category' => '显示器', 'status' => '闲置'],
        ];

        foreach ($assets as $data) {
            Asset::create($data);
        }

        // 打印模板
        $this->call(DictionarySeeder::class);
        $this->call(PrintTemplateSeeder::class);
    }
}
