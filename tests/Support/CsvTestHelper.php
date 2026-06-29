<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

trait CsvTestHelper
{
    /**
     * 创建临时 CSV 文件并返回路径
     */
    protected function createTempCsv(array $headers, array $rows, string $encoding = 'UTF-8', bool $bom = false): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv_test_') . '.csv';
        $handle = fopen($tmp, 'w');

        if ($bom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        if ($encoding !== 'UTF-8') {
            $content = file_get_contents($tmp);
            $content = mb_convert_encoding($content, $encoding, 'UTF-8');
            file_put_contents($tmp, $content);
        }

        return $tmp;
    }

    /**
     * 创建空的临时文件（0 字节）
     */
    protected function createEmptyTempFile(string $ext = 'csv'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'empty_test_');
        // Rename to have correct extension
        $newPath = $tmp . '.' . $ext;
        rename($tmp, $newPath);
        return $newPath;
    }

    /**
     * 从路径创建 UploadedFile 实例
     */
    protected function createUploadedFile(string $path, string $mimeType = 'text/csv'): UploadedFile
    {
        return new UploadedFile(
            $path,
            basename($path),
            $mimeType,
            null,
            true // test mode
        );
    }

    /**
     * 快速创建 CSV 并返回 UploadedFile
     */
    protected function createCsvUpload(array $headers, array $rows, string $encoding = 'UTF-8', bool $bom = false): UploadedFile
    {
        $path = $this->createTempCsv($headers, $rows, $encoding, $bom);
        return $this->createUploadedFile($path);
    }

    /**
     * 创建指定内容的临时文件并返回 UploadedFile
     */
    protected function createRawFileUpload(string $content, string $filename = 'test.csv', string $mimeType = 'text/csv'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'raw_test_') . '_' . $filename;
        file_put_contents($tmp, $content);
        return $this->createUploadedFile($tmp, $mimeType);
    }

    /**
     * 种子测试用 DepartmentCode 数据
     */
    protected function seedDepartmentCodes(): void
    {
        \App\Models\DepartmentCode::insert([
            ['type' => 'department', 'code' => '01', 'name' => '办公室', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'department', 'code' => '02', 'name' => '信息中心', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'department', 'code' => '03', 'name' => '财务部', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'code' => 'C01', 'name' => '台式计算机（国产）', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'code' => 'C02', 'name' => '台式计算机（非国产）', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'code' => 'C03', 'name' => '打印机', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'code' => 'C04', 'name' => '服务器', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'status', 'code' => 'S01', 'name' => '在用', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'status', 'code' => 'S02', 'name' => '闲置', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'status', 'code' => 'S03', 'name' => '维修', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * 标准中文表头
     */
    protected function chineseHeaders(): array
    {
        return ['序号', '自有编号', '财务编码', '资产名称', '部门', '房间号', 'IP地址', 'MAC地址', 'SN序列号', '品牌', '规格型号', '类别', '状态', '使用人', '备注'];
    }

    /**
     * 标准英文表头
     */
    protected function englishHeaders(): array
    {
        return ['asset_code', 'financial_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];
    }

    /**
     * 创建一个标准的测试用户（管理员）
     */
    protected function createAdmin(): \App\Models\User
    {
        return \App\Models\User::create([
            'name' => '测试管理员',
            'username' => 'testadmin',
            'email' => 'testadmin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department' => '信息中心',
            'is_active' => true,
        ]);
    }

    /**
     * 创建一个普通用户
     */
    protected function createNormalUser(): \App\Models\User
    {
        return \App\Models\User::create([
            'name' => '测试用户',
            'username' => 'testuser',
            'email' => 'testuser@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'department' => '财务部',
            'is_active' => true,
        ]);
    }

    /**
     * 生成一个标准数据行（中文表头顺序）
     */
    protected function assetRow(array $overrides = []): array
    {
        $defaults = [
            '1',           // 序号
            '',            // 自有编号
            '',            // 财务编码
            '测试资产',     // 资产名称
            '办公室',       // 部门
            '301',         // 房间号
            '192.168.1.100', // IP地址
            'AA:BB:CC:DD:EE:FF', // MAC地址
            'SN001',       // SN序列号
            '联想',        // 品牌
            '启天M428',    // 规格型号
            '台式计算机（国产）', // 类别
            '在用',        // 状态
            '张三',        // 使用人
            '',            // 备注
        ];
        return array_replace($defaults, $overrides);
    }
}
