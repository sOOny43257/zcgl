<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use App\Models\Asset;
use App\Models\AssetIntake;
use App\Models\TransferOrder;
use App\Models\AssetBorrow;
use App\Models\AssetDisposal;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableUsage;
use App\Models\ConsumableInventory;
use App\Models\DepartmentCode;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    /**
     * Unified print endpoint: /print/{module}/{id}
     */
    public function print(Request $request, string $module, int $id)
    {
        $template = PrintTemplate::forModule($module);
        if (!$template) {
            abort(404, '该单据类型未配置打印模板');
        }

        $data = match ($module) {
            'intake' => $this->buildIntakeData($id),
            'transfer' => $this->buildTransferData($id),
            'borrow' => $this->buildBorrowData($id),
            'disposal' => $this->buildDisposalData($id),
            'consumable_intake' => $this->buildConsumableIntakeData($id),
            'consumable_usage' => $this->buildConsumableUsageData($id),
            'consumable_inventory' => $this->buildConsumableInventoryData($id),
            default => abort(404, '不支持的单据类型'),
        };

        return view('print.universal', array_merge($data, ['template' => $template]));
    }

    private function buildIntakeData(int $id): array
    {
        $intake = AssetIntake::findOrFail($id);
        $assets = $intake->assets;
        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');

        $rows = $assets->map(fn($a) => [
            'asset_code' => $a->asset_code,
            'name' => $a->name,
            'category' => $catMap[$a->category] ?? $a->category,
            'brand' => $a->brand ?? '-',
            'model' => $a->model ?? '-',
            'sn' => $a->sn ?? '-',
            'department' => $deptMap[$a->department] ?? $a->department,
            'room' => $a->room ?? '-',
            'user' => $a->user ?? '-',
            'purchase_price' => $a->purchase_price ? number_format($a->purchase_price, 2) : '-',
        ])->toArray();

        return [
            'orderNo' => $intake->order_no,
            'metaValues' => [
                '入库日期' => $intake->intake_date?->format('Y-m-d'),
                '供应商' => $intake->supplier,
                '采购单号' => $intake->purchase_order_no,
                '总金额' => $intake->total_amount ? '¥' . number_format($intake->total_amount, 2) : null,
                '经办人' => $intake->operator,
                '验收人' => $intake->approver,
                '备注' => $intake->remarks,
            ],
            'rows' => $rows,
            'showTotal' => true,
            'totalLabel' => '合计 ' . count($rows) . ' 项',
            'totalFields' => [],
        ];
    }

    private function buildTransferData(int $id): array
    {
        $order = TransferOrder::findOrFail($id);
        $asset = Asset::find($order->asset_id);
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');

        $rows = [];
        if ($asset) {
            $rows[] = [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category,
                'brand' => $asset->brand ?? '-',
                'model' => $asset->model ?? '-',
                'sn' => $asset->sn ?? '-',
                'from_dept' => $deptMap[$order->from_dept] ?? $order->from_dept ?? '-',
                'to_dept' => $deptMap[$order->to_dept] ?? $order->to_dept ?? '-',
                'room' => $asset->room ?? '-',
            ];
        }

        return [
            'orderNo' => $order->order_no,
            'metaValues' => [
                '调拨日期' => $order->created_at?->format('Y-m-d'),
                '调出部门' => $deptMap[$order->from_dept] ?? $order->from_dept,
                '调入部门' => $deptMap[$order->to_dept] ?? $order->to_dept,
                '调出人' => $order->from_user,
                '调入人' => $order->to_user,
                '经办人' => $order->operator,
            ],
            'rows' => $rows,
        ];
    }

    private function buildBorrowData(int $id): array
    {
        $borrow = AssetBorrow::findOrFail($id);
        $asset = $borrow->asset;
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');

        $rows = [];
        if ($asset) {
            $rows[] = [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category,
                'brand' => $asset->brand ?? '-',
                'model' => $asset->model ?? '-',
                'sn' => $asset->sn ?? '-',
                'department' => $deptMap[$asset->department] ?? $asset->department,
                'user' => $asset->user ?? '-',
            ];
        }

        return [
            'orderNo' => $borrow->order_no,
            'metaValues' => [
                '借用日期' => $borrow->borrow_date?->format('Y-m-d'),
                '预计归还' => $borrow->expected_return_date?->format('Y-m-d'),
                '借用人' => $borrow->borrower,
                '借用部门' => $deptMap[$borrow->department] ?? $borrow->department,
                '事由' => $borrow->remarks,
            ],
            'rows' => $rows,
        ];
    }

    private function buildDisposalData(int $id): array
    {
        $disposal = AssetDisposal::findOrFail($id);
        $assetIds = $disposal->draft_data['asset_ids'] ?? [];
        $assets = Asset::whereIn('id', $assetIds)->get();
        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');
        $statusMap = DepartmentCode::type('status')->pluck('name', 'code');

        $rows = $assets->map(fn($a) => [
            'asset_code' => $a->asset_code,
            'name' => $a->name,
            'category' => $catMap[$a->category] ?? $a->category,
            'brand' => $a->brand ?? '-',
            'model' => $a->model ?? '-',
            'sn' => $a->sn ?? '-',
            'department' => $deptMap[$a->department] ?? $a->department,
            'status' => $statusMap[$a->status] ?? $a->status,
            'user' => $a->user ?? '-',
        ])->toArray();

        return [
            'orderNo' => $disposal->order_no,
            'metaValues' => [
                '报废日期' => $disposal->disposal_date?->format('Y-m-d'),
                '处置方式' => $disposal->disposal_method,
                '报废原因' => $disposal->reason,
                '经办人' => $disposal->operator,
                '审批人' => $disposal->approver,
                '备注' => $disposal->remarks,
            ],
            'rows' => $rows,
            'showTotal' => true,
            'totalLabel' => '合计 ' . count($rows) . ' 项',
            'totalFields' => [],
        ];
    }

    private function buildConsumableIntakeData(int $id): array
    {
        $order = ConsumableIntakeOrder::findOrFail($id);
        $order->load('items.consumable');

        $rows = $order->items->map(fn($item) => [
            'name' => $item->consumable->name ?? '-',
            'spec' => $item->consumable->spec ?? '-',
            'unit_name' => $item->consumable?->unitName() ?? '-',
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price ? number_format($item->unit_price, 2) : '-',
            'subtotal' => $item->subtotal ? number_format($item->subtotal, 2) : '-',
        ])->toArray();

        return [
            'orderNo' => $order->order_no,
            'metaValues' => [
                '入库日期' => $order->intake_date?->format('Y-m-d'),
                '供应商' => $order->supplierName(),
                '经办人' => $order->operator_name,
                '备注' => $order->remarks,
            ],
            'rows' => $rows,
            'showTotal' => true,
            'totalLabel' => '合计 ' . count($rows) . ' 种',
            'totalFields' => ['quantity'],
        ];
    }

    private function buildConsumableUsageData(int $id): array
    {
        $usage = ConsumableUsage::findOrFail($id);
        $usage->load('consumable');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');

        $rows = [[
            'name' => $usage->consumable->name ?? '-',
            'spec' => $usage->consumable->spec ?? '-',
            'unit_name' => $usage->consumable?->unitName() ?? '-',
            'quantity' => $usage->quantity,
        ]];

        return [
            'orderNo' => '',
            'metaValues' => [
                '领用日期' => $usage->usage_date?->format('Y-m-d'),
                '使用部门' => $deptMap[$usage->department_code] ?? $usage->department_code,
                '领用事由' => $usage->reason,
                '经办人' => $usage->operator_name,
            ],
            'rows' => $rows,
        ];
    }

    private function buildConsumableInventoryData(int $id): array
    {
        $inventory = ConsumableInventory::findOrFail($id);
        $inventory->load('items.consumable');

        $rows = $inventory->items->map(fn($item) => [
            'name' => $item->consumable->name ?? '-',
            'spec' => $item->consumable->spec ?? '-',
            'unit_name' => $item->consumable?->unitName() ?? '-',
            'book_quantity' => $item->book_quantity,
            'actual_quantity' => $item->actual_quantity,
            'difference' => ($item->difference > 0 ? '+' : '') . $item->difference,
            'reason' => $item->reason ?? '-',
        ])->toArray();

        return [
            'orderNo' => $inventory->inventory_no,
            'metaValues' => [
                '盘点日期' => $inventory->inventory_date?->format('Y-m-d'),
                '盘点人' => $inventory->operator_name,
                '备注' => $inventory->remarks,
            ],
            'rows' => $rows,
        ];
    }
}
