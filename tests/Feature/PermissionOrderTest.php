<?php

namespace Tests\Feature;

use App\Models\PermissionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermissionOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::forceCreate([
            'name' => '管理员',
            'username' => 'perm_admin',
            'email' => 'perm_admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function realDocx(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx') . '.docx';
        copy(base_path('tests/fixtures/test_permission_order_sample.docx'), $tmp);
        return new UploadedFile($tmp, 'permission.docx', null, null, true);
    }

    public function test_parse_endpoint_extracts_fields(): void
    {
        $this->actingAs($this->admin());

        $response = $this->postJson(route('permission-orders.parse'), [
            'source_doc' => $this->realDocx(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['parsed', 'source_file_name']);
        $response->assertJsonPath('parsed.department', '南营门税务所');
        $response->assertJsonPath('parsed.fill_date', '2026年 1月 28日');
        $response->assertJsonPath('parsed.items.0.business_system', '金三系统');
        $response->assertJsonPath('parsed.items.0.names', '张博宇；贾楠；王朗；王云鹤；张伟奕；孙治政；马卓妮');
    }

    public function test_parse_rejects_non_docx(): void
    {
        $this->actingAs($this->admin());

        $txtFile = UploadedFile::fake()->create('test.txt', 100);

        $this->postJson(route('permission-orders.parse'), [
            'source_doc' => $txtFile,
        ])->assertStatus(422);
    }

    public function test_store_draft_from_docx_and_update_and_void(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->post(route('permission-orders.store'), [
            '_action' => 'draft',
            'source_doc' => $this->realDocx(),
        ]);

        $response->assertSessionHasNoErrors();

        $order = PermissionOrder::first();
        $this->assertNotNull($order, 'PermissionOrder record should be created');
        $this->assertEquals('draft', $order->status);
        $this->assertEquals('南营门税务所', $order->department);
        $this->assertEquals('2026年 1月 28日', $order->fill_date);
        $this->assertNotNull($order->items);
        $this->assertCount(1, $order->items);
        $this->assertEquals('金三系统', $order->items[0]['business_system']);
        $this->assertStringContainsString('张博宇', $order->items[0]['names']);
        $this->assertNotNull($order->source_doc_path);
        Storage::disk('public')->assertExists($order->source_doc_path);

        // Update the draft
        $this->put(route('permission-orders.update', $order), [
            '_action' => 'draft',
            'department' => '南营门税务所(修改)',
            'fill_date' => '2026年 2月 1日',
            'items_json' => json_encode([
                ['names' => '张博宇；贾楠', 'business_system' => '金三系统', 'original_position' => '', 'added_position' => '新增岗位A', 'removed_position' => ''],
                ['names' => '王朗', 'business_system' => '电子税务局', 'original_position' => '原岗位B', 'added_position' => '', 'removed_position' => '减少岗位C'],
            ]),
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals('南营门税务所(修改)', $order->department);
        $this->assertCount(2, $order->items);
        $this->assertEquals('电子税务局', $order->items[1]['business_system']);

        // Toggle paper submitted
        $this->post(route('permission-orders.togglePaper', $order))
            ->assertJson(['paper_submitted' => true]);
        $order->refresh();
        $this->assertTrue($order->paper_submitted);

        // Toggle back
        $this->post(route('permission-orders.togglePaper', $order))
            ->assertJson(['paper_submitted' => false]);
        $order->refresh();
        $this->assertFalse($order->paper_submitted);

        // Submit void
        $this->post(route('permission-orders.void', $order), [
            'voided_by' => '系统测试员',
            'voided_at' => now()->format('Y-m-d H:i:s'),
            'paper_submitted' => true,
            'department' => '南营门税务所(修改)',
        ])->assertRedirect(route('permission-orders.index'));

        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertEquals('系统测试员', $order->voided_by);
        $this->assertNotNull($order->order_no);
        $this->assertStringStartsWith('QXD-', $order->order_no);
        $this->assertTrue($order->paper_submitted);
    }

    public function test_non_admin_cannot_access_module(): void
    {
        $user = User::forceCreate([
            'name' => '普通用户',
            'username' => 'perm_user2',
            'email' => 'perm_user2@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->actingAs($user)->get(route('permission-orders.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('permission-orders.create'))->assertStatus(403);
        $this->actingAs($user)->post(route('permission-orders.store'))->assertStatus(403);
        $this->actingAs($user)->postJson(route('permission-orders.parse'))->assertStatus(403);
    }

    public function test_reject_non_docx_upload(): void
    {
        $this->actingAs($this->admin());

        $txtFile = UploadedFile::fake()->create('test.txt', 100);

        $this->post(route('permission-orders.store'), [
            '_action' => 'draft',
            'source_doc' => $txtFile,
        ])->assertSessionHas('error');
    }

    public function test_show_page_displays_order_details(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post(route('permission-orders.store'), [
            '_action' => 'draft',
            'source_doc' => $this->realDocx(),
        ]);

        $order = PermissionOrder::first();
        $this->assertNotNull($order);

        // Show draft
        $response = $this->get(route('permission-orders.show', $order));
        $response->assertOk();
        $response->assertSee($order->department);
        $response->assertSee('草稿');

        // Void it
        $this->post(route('permission-orders.void', $order), [
            'voided_by' => '测试员',
            'voided_at' => now()->format('Y-m-d H:i:s'),
            'department' => $order->department,
        ]);

        $order->refresh();
        $response = $this->get(route('permission-orders.show', $order));
        $response->assertOk();
        $response->assertSee($order->order_no);
        $response->assertSee('已作废');
        $response->assertSee('测试员');
    }
}
