<?php

namespace App\Http\Controllers;

use App\Services\ConsumableReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsumableReportController extends Controller
{
    public function index(Request $request, ConsumableReportService $service)
    {
        $yearMonth = $request->get('month', date('Y-m'));
        $reportType = $request->get('type', 'department');

        $data = match ($reportType) {
            'department' => $service->departmentStats($yearMonth),
            'ranking' => $service->consumableRanking($yearMonth),
            'turnover' => $service->turnoverReport($yearMonth),
            default => [],
        };

        return view('consumable-reports.index', compact('data', 'yearMonth', 'reportType'));
    }

    public function export(Request $request, ConsumableReportService $service): StreamedResponse
    {
        $yearMonth = $request->get('month', date('Y-m'));
        $reportType = $request->get('type', 'department');

        $csv = $service->exportCsv($yearMonth, $reportType);

        $filename = match ($reportType) {
            'department' => "部门消耗统计_{$yearMonth}.csv",
            'ranking' => "耗材消耗排行_{$yearMonth}.csv",
            'turnover' => "进销存报表_{$yearMonth}.csv",
            default => "耗材报表_{$yearMonth}.csv",
        };

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
