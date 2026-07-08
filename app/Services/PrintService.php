<?php

namespace App\Services;

use App\Models\PrintTemplate;

class PrintService
{
    /**
     * Module metadata: labels, default configs, column options.
     */
    public static function moduleMeta(): array
    {
        return [
            'intake' => [
                'label' => '资产入库单',
                'page_meta_options' => ['入库日期', '供应商', '采购单号', '总金额', '经办人', '验收人', '备注'],
                'column_options' => [
                    'asset_code' => '资产编号', 'name' => '资产名称', 'category' => '类别',
                    'brand' => '品牌', 'model' => '规格型号', 'sn' => 'SN序列号',
                    'department' => '部门', 'room' => '房间号', 'user' => '使用人', 'purchase_price' => '单价',
                ],
            ],
            'transfer' => [
                'label' => '资产调拨单',
                'page_meta_options' => ['调拨日期', '调出部门', '调入部门', '调出人', '调入人', '经办人', '备注'],
                'column_options' => [
                    'asset_code' => '资产编号', 'name' => '资产名称', 'category' => '类别',
                    'brand' => '品牌', 'model' => '规格型号', 'sn' => 'SN序列号',
                    'from_dept' => '调出部门', 'to_dept' => '调入部门', 'room' => '房间号',
                ],
            ],
            'borrow' => [
                'label' => '设备借用单',
                'page_meta_options' => ['借用日期', '预计归还', '借用人', '借用部门', '事由', '备注'],
                'column_options' => [
                    'asset_code' => '资产编号', 'name' => '资产名称', 'category' => '类别',
                    'brand' => '品牌', 'model' => '规格型号', 'sn' => 'SN序列号',
                    'department' => '部门', 'user' => '使用人',
                ],
            ],
            'disposal' => [
                'label' => '资产报废单',
                'page_meta_options' => ['报废日期', '处置方式', '报废原因', '经办人', '审批人', '备注'],
                'column_options' => [
                    'asset_code' => '资产编号', 'name' => '资产名称', 'category' => '类别',
                    'brand' => '品牌', 'model' => '规格型号', 'sn' => 'SN序列号',
                    'department' => '部门', 'status' => '状态', 'user' => '使用人',
                ],
            ],
            'consumable_intake' => [
                'label' => '耗材入库单',
                'page_meta_options' => ['入库日期', '供应商', '经办人', '备注'],
                'column_options' => [
                    'name' => '耗材名称', 'spec' => '规格型号', 'unit_name' => '单位',
                    'quantity' => '入库数量', 'unit_price' => '单价', 'subtotal' => '小计',
                ],
            ],
            'consumable_usage' => [
                'label' => '耗材领用单',
                'page_meta_options' => ['领用日期', '使用部门', '领用事由', '经办人', '备注'],
                'column_options' => [
                    'name' => '耗材名称', 'spec' => '规格型号', 'unit_name' => '单位',
                    'quantity' => '领用数量',
                ],
            ],
            'consumable_inventory' => [
                'label' => '耗材盘点单',
                'page_meta_options' => ['盘点日期', '盘点人', '备注'],
                'column_options' => [
                    'name' => '耗材名称', 'spec' => '规格型号', 'unit_name' => '单位',
                    'book_quantity' => '账面库存', 'actual_quantity' => '实际数量',
                    'difference' => '差异', 'reason' => '差异原因',
                ],
            ],
        ];
    }

    /**
     * Get column options for a module.
     */
    public static function columnOptions(string $module): array
    {
        $meta = static::moduleMeta();
        return $meta[$module]['column_options'] ?? [];
    }

    /**
     * Get page meta options for a module.
     */
    public static function pageMetaOptions(string $module): array
    {
        $meta = static::moduleMeta();
        return $meta[$module]['page_meta_options'] ?? [];
    }

    /**
     * Get default config for a module.
     */
    public static function defaultConfig(string $module): array
    {
        $meta = static::moduleMeta();
        $m = $meta[$module] ?? null;
        if (!$m) return [];

        $columns = [];
        foreach (array_slice($m['column_options'], 0, 6, true) as $key => $label) {
            $columns[] = ['key' => $key, 'label' => $label];
        }

        return [
            'page' => [
                'title' => $m['label'],
                'order_no_prefix' => '单号：',
                'meta' => array_slice($m['page_meta_options'], 0, 4),
            ],
            'table' => [
                'show_index' => true,
                'show_total' => false,
                'columns' => $columns,
            ],
            'signatures' => ['经办人', '验收人'],
            'footer' => ['text' => '', 'show_date' => true],
        ];
    }

    /**
     * Get the module label.
     */
    public static function moduleLabel(string $module): string
    {
        $meta = static::moduleMeta();
        return $meta[$module]['label'] ?? $module;
    }
}
