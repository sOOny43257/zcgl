<?php

namespace Tests\Feature\CsvImport;

use App\Models\Asset;
use App\Models\DepartmentCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\CsvTestHelper;

class ParseCsvTest extends TestCase
{
    use RefreshDatabase, CsvTestHelper;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDepartmentCodes();
        $this->admin = $this->createAdmin();
    }

    // ==================== A. 文件级别 ====================

    public function test_a1_reject_non_csv_file()
    {
        // Use binary content that won't match csv/txt MIME type
        $binaryContent = "\x50\x4B\x03\x04" . str_repeat("\x00", 100); // ZIP-like header
        $file = $this->createRawFileUpload($binaryContent, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $file]);
        $response->assertStatus(422);
    }

    public function test_a2_reject_missing_file()
    {
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', []);
        $response->assertStatus(422);
    }

    public function test_a3_reject_empty_file()
    {
        $path = $this->createEmptyTempFile();
        $file = $this->createUploadedFile($path);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $file]);
        // Empty file fails mimes validation (0 bytes MIME undetectable)
        $response->assertStatus(422);
    }

    public function test_a4_header_only_no_data()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), []);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['total']);
        $this->assertEmpty($data['rows']);
    }

    public function test_a5_bom_prefixed_utf8()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ], 'UTF-8', true);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('测试资产', $data['rows'][0]['name']);
    }

    public function test_a6_gbk_encoding()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ], 'GBK');
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('测试资产', $data['rows'][0]['name']);
    }

    // ==================== B. 表头/列名 ====================

    public function test_b1_chinese_headers()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $row = $response->json('rows')[0];
        // 序号列被忽略，自有编号为空
        $this->assertEquals('', $row['asset_code']);
        $this->assertEquals('测试资产', $row['name']);
        $this->assertEquals('301', $row['room']);
    }

    public function test_b2_english_headers()
    {
        $upload = $this->createCsvUpload($this->englishHeaders(), [
            ['', '', 'Test Asset', '01', '101', '192.168.1.1', 'AA:BB:CC:DD:EE:01', 'SN001', 'Dell', 'OptiPlex', 'C01', 'S01', 'Tom', 'note'],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $row = $response->json('rows')[0];
        $this->assertEquals('Test Asset', $row['name']);
        $this->assertEquals('192.168.1.1', $row['ip']);
    }

    public function test_b3_mixed_case_headers()
    {
        $headers = ['Asset_Code', 'Financial_Code', 'NAME', 'Department', 'Room', 'IP', 'MAC', 'SN', 'Brand', 'Model', 'Category', 'Status', 'User', 'Remarks'];
        $upload = $this->createCsvUpload($headers, [
            ['', '', 'Mixed Case Test', '01', '101', '', '', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals('Mixed Case Test', $response->json('rows.0.name'));
    }

    public function test_b4_headers_with_spaces()
    {
        $headers = [' 自有编号 ', ' 资产名称 ', ' 部门 '];
        $upload = $this->createCsvUpload($headers, [
            ['', 'Space Test', '办公室'],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals('Space Test', $response->json('rows.0.name'));
    }

    public function test_b5_missing_most_columns()
    {
        $headers = ['资产名称', '部门'];
        $upload = $this->createCsvUpload($headers, [
            ['Only Name', '办公室'],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $row = $response->json('rows')[0];
        $this->assertEquals('Only Name', $row['name']);
        $this->assertEquals('', $row['ip']);
        $this->assertEquals('', $row['mac']);
        $this->assertEquals('', $row['room']);
    }

    public function test_b6_wrong_column_names()
    {
        $headers = ['foo', 'bar', 'baz'];
        $upload = $this->createCsvUpload($headers, [
            ['val1', 'val2', 'val3'],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        // 所有列名无法映射，业务字段全为空，空行被跳过
        $this->assertEquals(0, $response->json('total'));
        $this->assertEmpty($response->json('rows'));
    }

    public function test_b7_extra_unknown_columns()
    {
        $headers = ['资产名称', '部门', '未知列X', '另一个未知列'];
        $upload = $this->createCsvUpload($headers, [
            ['Extra Col Test', '办公室', 'ignored1', 'ignored2'],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals('Extra Col Test', $response->json('rows.0.name'));
        $this->assertEquals(1, $response->json('total'));
    }

    // ==================== C. 空数据/缺失数据 ====================

    public function test_c1_empty_row_skipped()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([3 => '正常资产']),      // row 1: valid
            ['2', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // row 2: all empty
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('正常资产', $data['rows'][0]['name']);
    }

    public function test_c2_only_name_filled()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            ['1', '', '', '办公室台式机-01', '', '', '', '', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $row = $response->json('rows')[0];
        $this->assertTrue($row['_valid']);
        $this->assertEquals('办公室台式机-01', $row['name']);
    }

    public function test_c3_ip_empty_mac_empty()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            ['1', '', '', 'No IP MAC', '办公室', '301', '', '', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_c4_ip_empty_mac_filled()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            ['1', '', '', 'MAC Only', '办公室', '301', '', 'AA:BB:CC:DD:EE:FF', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_c5_ip_filled_mac_empty()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            ['1', '', '', 'IP Only', '办公室', '301', '192.168.1.1', '', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_c6_consecutive_empty_rows()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            ['1', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['2', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['3', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
    }

    // ==================== D. IP 格式验证 ====================

    public function test_d1_valid_ipv4()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '192.168.1.1']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
        $this->assertEquals('192.168.1.1', $response->json('rows.0.ip'));
    }

    public function test_d2_valid_ipv6()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '::1']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_d3_invalid_ip_out_of_range()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '999.999.999.999']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        $this->assertStringContainsString('IP格式不合法', $response->json('rows.0._fieldErrors.ip'));
    }

    public function test_d4_invalid_ip_letters()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => 'abc.def.ghi.jkl']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_d5_invalid_ip_missing_octet()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '192.168.1']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_d6_invalid_ip_extra_octet()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '192.168.1.1.1']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_d7_invalid_ip_with_port()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => '192.168.1.1:8080']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_d8_ip_with_spaces_trimmed()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([6 => ' 192.168.1.1 ']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        // parseCsv 先 trim 数据，所以 trim 后是合法 IP
        $this->assertTrue($response->json('rows.0._valid'));
    }

    // ==================== E. MAC 格式验证 ====================

    public function test_e1_valid_mac_colon()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'AA:BB:CC:DD:EE:FF']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_e2_valid_mac_dash()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'AA-BB-CC-DD-EE-FF']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_e3_valid_mac_lowercase()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'aa:bb:cc:dd:ee:ff']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_e4_invalid_mac_too_short()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'AA:BB:CC:DD:EE']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        $this->assertStringContainsString('MAC格式不合法', $response->json('rows.0._fieldErrors.mac'));
    }

    public function test_e5_invalid_mac_too_long()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'AA:BB:CC:DD:EE:FF:00']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_e6_invalid_mac_non_hex()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'GG:HH:II:JJ:KK:LL']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
    }

    public function test_e7_mac_mixed_delimiters_accepted_by_regex()
    {
        // 当前正则 ^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$ 每个分隔符独立匹配 : 或 -
        // 所以混合分隔符实际上是被接受的 —— 这是代码的已知行为
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([7 => 'AA:BB:CC-DD-EE-FF']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_e8_duplicate_mac_not_checked()
    {
        // 两行使用相同 MAC，不做重复性检查，两行都应合法
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([3 => '资产1', 7 => 'AA:BB:CC:DD:EE:FF']),
            $this->assetRow([3 => '资产2', 7 => 'AA:BB:CC:DD:EE:FF']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('rows.0._valid'));
        $this->assertTrue($response->json('rows.1._valid'));
        $this->assertEquals(2, $response->json('valid'));
    }

    // ==================== F. 数据字典验证 ====================

    public function test_f1_department_by_chinese_name()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([4 => '办公室']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
        // 应被转换为编码 '01'
        $this->assertEquals('01', $response->json('rows.0.department'));
    }

    public function test_f2_department_by_code()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([4 => '01']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
        $this->assertEquals('01', $response->json('rows.0.department'));
    }

    public function test_f3_invalid_department()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([4 => '火星基地']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        $this->assertStringContainsString('火星基地', $response->json('rows.0._fieldErrors.department'));
        $this->assertStringContainsString('不在数据字典中', $response->json('rows.0._fieldErrors.department'));
    }

    public function test_f4_similar_department_suggestion()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([4 => '办公室公']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        // 应包含建议
        $this->assertStringContainsString('建议', $response->json('rows.0._fieldErrors.department'));
    }

    public function test_f5_empty_department_skipped()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([4 => '']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertTrue($response->json('rows.0._valid'));
    }

    public function test_f6_invalid_category()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([11 => '服务器集群']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        $this->assertStringContainsString('服务器集群', $response->json('rows.0._fieldErrors.category'));
    }

    public function test_f7_invalid_status()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([12 => '已损坏']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $this->assertFalse($response->json('rows.0._valid'));
        $this->assertStringContainsString('已损坏', $response->json('rows.0._fieldErrors.status'));
    }

    // ==================== 额外: 多行混合验证 ====================

    public function test_mixed_valid_and_invalid_rows()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([3 => '合法资产', 6 => '192.168.1.1']),
            $this->assetRow([3 => '非法IP', 6 => '999.999.999.999']),
            $this->assetRow([3 => '合法无IP', 6 => '']),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['total']);
        $this->assertEquals(2, $data['valid']);
        $this->assertTrue($data['rows'][0]['_valid']);
        $this->assertFalse($data['rows'][1]['_valid']);
        $this->assertTrue($data['rows'][2]['_valid']);
    }

    public function test_response_structure()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ]);
        $response = $this->actingAs($this->admin)->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'columns',
            'rows' => [
                '*' => [
                    'asset_code', 'financial_code', 'name', 'department', 'room',
                    'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status',
                    'user', 'remarks', '_row', '_valid', '_errors', '_fieldErrors', '_display',
                ]
            ],
            'total',
            'valid',
        ]);
    }
}
