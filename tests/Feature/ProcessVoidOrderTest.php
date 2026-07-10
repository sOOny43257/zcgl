<?php

namespace Tests\Feature;

use App\Models\ProcessVoidOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessVoidOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::forceCreate([
            'name' => '管理员',
            'username' => 'process_admin',
            'email' => 'process_admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function realDocx(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx') . '.docx';
        copy(base_path('tests/fixtures/test_process_void_sample.docx'), $tmp);
        return new UploadedFile($tmp, 'process.docx', null, null, true);
    }

    public function test_parse_endpoint_extracts_fields(): void
    {
        $this->actingAs($this->admin());

        $response = $this->postJson(route('process-void-orders.parse'), [
            'source_doc' => $this->realDocx(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['parsed', 'source_file_name']);
        $response->assertJsonPath('parsed.company_name', '天津智一达科技有限公司');
        $response->assertJsonPath('parsed.tax_no', '91120111MA06M7407R');
        $response->assertJsonPath('parsed.department', '南营门所');
        $response->assertJsonPath('parsed.submitter_sign', '郭彤');
        $response->assertJsonPath('parsed.department_chief_sign', '孙国枢');
    }

    public function test_parse_rejects_non_docx(): void
    {
        $this->actingAs($this->admin());

        $txtFile = UploadedFile::fake()->create('test.txt', 100);

        $this->postJson(route('process-void-orders.parse'), [
            'source_doc' => $txtFile,
        ])->assertStatus(422);
    }

    public function test_store_draft_from_docx_and_update_and_void(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->post(route('process-void-orders.store'), [
            '_action' => 'draft',
            'source_doc' => $this->realDocx(),
        ]);

        $response->assertSessionHasNoErrors();

        $order = ProcessVoidOrder::first();
        $this->assertNotNull($order, 'ProcessVoidOrder record should be created');
        $this->assertEquals('draft', $order->status);
        $this->assertEquals('天津智一达科技有限公司', $order->company_name);
        $this->assertEquals('91120111MA06M7407R', $order->tax_no);
        $this->assertEquals('南营门所', $order->department);
        $this->assertEquals('2023-02-10', $order->flow_start_time);
        $this->assertStringContainsString('退抵税费审批', $order->process_name);
        $this->assertEquals('资料无法按时提供。', $order->termination_reason);
        $this->assertEquals('郭彤', $order->submitter_sign);
        $this->assertEquals('孙国枢', $order->department_chief_sign);
        $this->assertNotNull($order->source_doc_path);
        Storage::disk('public')->assertExists($order->source_doc_path);

        // Update the draft
        $this->put(route('process-void-orders.update', $order), [
            '_action' => 'draft',
            'department' => '南营门所(修改)',
            'flow_start_time' => '2023-02-10',
            'company_name' => '天津智一达科技有限公司',
            'tax_no' => '91120111MA06M7407R',
            'process_name' => '退抵税费审批',
            'termination_reason' => '资料无法按时提供。',
            'submitter_sign' => '郭彤(修改)',
            'department_chief_sign' => '孙国枢(修改)',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals('南营门所(修改)', $order->department);
        $this->assertEquals('郭彤(修改)', $order->submitter_sign);
        $this->assertEquals('孙国枢(修改)', $order->department_chief_sign);

        // Toggle paper submitted
        $this->post(route('process-void-orders.togglePaper', $order))
            ->assertJson(['paper_submitted' => true]);
        $order->refresh();
        $this->assertTrue($order->paper_submitted);

        // Toggle back
        $this->post(route('process-void-orders.togglePaper', $order))
            ->assertJson(['paper_submitted' => false]);
        $order->refresh();
        $this->assertFalse($order->paper_submitted);

        // Submit void
        $this->post(route('process-void-orders.void', $order), [
            'voided_by' => '系统测试员',
            'voided_at' => now()->format('Y-m-d H:i:s'),
            'paper_submitted' => true,
            'department' => '南营门所(修改)',
            'company_name' => '天津智一达科技有限公司',
            'tax_no' => '91120111MA06M7407R',
            'process_name' => '退抵税费审批',
            'termination_reason' => '资料无法按时提供。',
        ])->assertRedirect(route('process-void-orders.index'));

        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertEquals('系统测试员', $order->voided_by);
        $this->assertNotNull($order->order_no);
        $this->assertStringStartsWith('LCZF-', $order->order_no);
        $this->assertTrue($order->paper_submitted);
    }

    public function test_non_admin_cannot_access_module(): void
    {
        $user = User::forceCreate([
            'name' => '普通用户',
            'username' => 'process_user2',
            'email' => 'process_user2@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->actingAs($user)->get(route('process-void-orders.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('process-void-orders.create'))->assertStatus(403);
        $this->actingAs($user)->post(route('process-void-orders.store'))->assertStatus(403);
        $this->actingAs($user)->postJson(route('process-void-orders.parse'))->assertStatus(403);
    }

    public function test_reject_non_docx_upload(): void
    {
        $this->actingAs($this->admin());

        $txtFile = UploadedFile::fake()->create('test.txt', 100);

        $this->post(route('process-void-orders.store'), [
            '_action' => 'draft',
            'source_doc' => $txtFile,
        ])->assertSessionHas('error');
    }
}
