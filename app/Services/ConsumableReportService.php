<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\ConsumableIntakeItem;
use App\Models\ConsumableIntakeOrder;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class ConsumableReportService
{
    /**
     * Department consumption stats for a given month.
     * Returns top 10 by total amount.
     */
    public function departmentStats(string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        return ConsumableUsage::selectRaw('department_code, SUM(quantity) as total_qty, COUNT(*) as usage_count')
            ->whereBetween('usage_date', [$start, $end])
            ->groupBy('department_code')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'department_code' => $row->department_code,
                    'department_name' => Asset::translateDept($row->department_code),
                    'total_qty' => (int) $row->total_qty,
                    'usage_count' => (int) $row->usage_count,
                ];
            })
            ->toArray();
    }

    /**
     * Consumable usage ranking for a given month.
     * Returns top 10 by quantity.
     */
    public function consumableRanking(string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        return ConsumableUsage::selectRaw('consumable_id, SUM(quantity) as total_qty, COUNT(*) as usage_count')
            ->whereBetween('usage_date', [$start, $end])
            ->groupBy('consumable_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $consumable = Consumable::find($row->consumable_id);
                return [
                    'consumable_id' => $row->consumable_id,
                    'consumable_name' => $consumable?->name ?? '已删除',
                    'spec' => $consumable?->spec ?? '',
                    'unit_name' => $consumable?->unitName() ?? '',
                    'total_qty' => (int) $row->total_qty,
                    'usage_count' => (int) $row->usage_count,
                    'current_stock' => $consumable?->current_stock ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Inventory turnover report for a given month.
     * Shows opening stock, intake, usage, closing stock for each consumable.
     */
    public function turnoverReport(string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        $consumables = Consumable::orderBy('name')->get();
        $result = [];

        foreach ($consumables as $c) {
            // Intake this month
            $intakeQty = ConsumableIntakeItem::whereHas('intakeOrder', function ($q) use ($start, $end) {
                $q->where('status', 'completed')->whereBetween('intake_date', [$start, $end]);
            })->where('consumable_id', $c->id)->sum('quantity');

            // Usage this month
            $usageQty = ConsumableUsage::where('consumable_id', $c->id)
                ->whereBetween('usage_date', [$start, $end])
                ->sum('quantity');

            // Closing stock = current stock (as of now)
            $closingStock = $c->current_stock;
            // Opening stock = closing - intake + usage
            $openingStock = $closingStock - (int) $intakeQty + (int) $usageQty;

            $result[] = [
                'consumable_id' => $c->id,
                'consumable_name' => $c->name,
                'spec' => $c->spec ?? '',
                'unit_name' => $c->unitName(),
                'opening_stock' => $openingStock,
                'intake_qty' => (int) $intakeQty,
                'usage_qty' => (int) $usageQty,
                'closing_stock' => $closingStock,
            ];
        }

        return $result;
    }

    /**
     * Export report data as CSV string.
     */
    public function exportCsv(string $yearMonth, string $reportType): string
    {
        switch ($reportType) {
            case 'department':
                $data = $this->departmentStats($yearMonth);
                $headers = ['部门编码', '部门名称', '领用总量', '领用次数'];
                $fields = ['department_code', 'department_name', 'total_qty', 'usage_count'];
                break;
            case 'ranking':
                $data = $this->consumableRanking($yearMonth);
                $headers = ['耗材名称', '规格型号', '单位', '消耗总量', '领用次数', '当前库存'];
                $fields = ['consumable_name', 'spec', 'unit_name', 'total_qty', 'usage_count', 'current_stock'];
                break;
            case 'turnover':
                $data = $this->turnoverReport($yearMonth);
                $headers = ['耗材名称', '规格型号', '单位', '期初库存', '本期入库', '本期出库', '期末库存'];
                $fields = ['consumable_name', 'spec', 'unit_name', 'opening_stock', 'intake_qty', 'usage_qty', 'closing_stock'];
                break;
            default:
                throw new \InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $output = fopen('php://temp', 'r+');
        // BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $headers);

        foreach ($data as $row) {
            $line = [];
            foreach ($fields as $f) {
                $line[] = $row[$f] ?? '';
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
