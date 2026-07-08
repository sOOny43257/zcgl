@props([
    'template' => null,
    'orderNo' => '',
    'metaValues' => [],  // ['入库日期' => '2026-07-08', '供应商' => '京东', ...]
    'columns' => [],     // rendered table columns (already translated)
    'rows' => [],        // array of arrays, each row is [col_key => value]
    'showIndex' => true,
    'showTotal' => false,
    'totalLabel' => '合计',
    'totalFields' => [], // ['quantity', 'subtotal'] fields to sum
    'signatures' => [],
    'footerText' => '',
    'showDate' => true,
])

@php
    $config = $template?->config ?? [];
    $title = data_get($config, 'page.title', '单据');
    $orderPrefix = data_get($config, 'page.order_no_prefix', '单号：');
    $metaFields = data_get($config, 'page.meta', []);
    $tableColumns = data_get($config, 'table.columns', $columns);
    $sigBlocks = data_get($config, 'signatures', $signatures);
    $ftText = data_get($config, 'footer.text', $footerText);
    $ftShowDate = data_get($config, 'footer.show_date', $showDate);
    $showIdx = data_get($config, 'table.show_index', $showIndex);
    $showTot = data_get($config, 'table.show_total', $showTotal);

    // Calculate totals
    $totals = [];
    if ($showTot && $totalFields) {
        foreach ($totalFields as $tf) {
            $totals[$tf] = 0;
            foreach ($rows as $row) {
                $totals[$tf] += is_numeric($row[$tf] ?? 0) ? $row[$tf] : 0;
            }
        }
    }
@endphp

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ ($template?->orientation ?? 'portrait') === 'landscape' ? 'A4 landscape' : 'A4 portrait' }};
            margin: 15mm 12mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "SimSun", "Songti SC", serif;
            font-size: 12px;
            color: #333;
            -webkit-print-color-adjust: exact;
        }
        .print-container { width: 100%; }
        .print-header {
            text-align: center;
            margin-bottom: 12px;
        }
        .print-header h1 {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 4px;
            margin-bottom: 6px;
        }
        .print-meta {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 10px;
            font-size: 12px;
            gap: 4px 24px;
        }
        .print-meta-item { white-space: nowrap; }
        .print-meta-item .label { color: #666; }
        .print-meta-item .value { color: #000; font-weight: 500; }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .print-table th,
        .print-table td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: center;
        }
        .print-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            white-space: nowrap;
        }
        .print-table td { text-align: center; }
        .print-table td.left { text-align: left; }
        .print-table .total-row td {
            font-weight: bold;
            background-color: #fafafa;
        }
        .print-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            font-size: 12px;
        }
        .signature-block {
            text-align: center;
            min-width: 120px;
        }
        .signature-block .sig-label {
            margin-bottom: 40px;
        }
        .signature-block .sig-line {
            border-top: 1px solid #333;
            padding-top: 4px;
        }
        .print-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #666;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        {{-- 页眉：标题 --}}
        <div class="print-header">
            <h1>{{ $title }}</h1>
        </div>

        {{-- 元信息 --}}
        <div class="print-meta">
            @if($orderNo)
                <div class="print-meta-item">
                    <span class="label">{{ $orderPrefix }}</span>
                    <span class="value">{{ $orderNo }}</span>
                </div>
            @endif
            @foreach($metaFields as $field)
                @if(isset($metaValues[$field]) && $metaValues[$field] !== '')
                    <div class="print-meta-item">
                        <span class="label">{{ $field }}：</span>
                        <span class="value">{{ $metaValues[$field] }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- 表格 --}}
        <table class="print-table">
            <thead>
                <tr>
                    @if($showIdx)
                        <th style="width:40px">序号</th>
                    @endif
                    @foreach($tableColumns as $col)
                        <th>{{ is_array($col) ? $col['label'] : $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                    <tr>
                        @if($showIdx)
                            <td>{{ $i + 1 }}</td>
                        @endif
                        @foreach($tableColumns as $col)
                            @php $key = is_array($col) ? $col['key'] : $col; @endphp
                            <td>{{ $row[$key] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
                @if($showTot && $totals)
                    <tr class="total-row">
                        @if($showIdx)
                            <td>{{ $totalLabel }}</td>
                        @endif
                        @foreach($tableColumns as $col)
                            @php $key = is_array($col) ? $col['key'] : $col; @endphp
                            <td>
                                @if(isset($totals[$key]))
                                    {{ is_float($totals[$key]) ? number_format($totals[$key], 2) : $totals[$key] }}
                                @else
                                    @if($loop->first && !$showIdx){{ $totalLabel }}@endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- 签名区 --}}
        @if($sigBlocks)
            <div class="print-signatures">
                @foreach($sigBlocks as $sig)
                    <div class="signature-block">
                        <div class="sig-label">{{ $sig }}：</div>
                        <div class="sig-line">（签字）</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 页脚 --}}
        <div class="print-footer">
            <span>{{ $ftText }}</span>
            @if($ftShowDate)
                <span>打印日期：{{ date('Y年m月d日') }}</span>
            @endif
        </div>
    </div>

    {{-- 打印按钮（屏幕显示，打印隐藏） --}}
    <div class="no-print" style="text-align:center; margin-top:30px;">
        <button onclick="window.print()" style="padding:8px 24px; background:#3b82f6; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:14px;">
            打印
        </button>
        <button onclick="window.close()" style="padding:8px 24px; background:#e5e7eb; color:#333; border:none; border-radius:8px; cursor:pointer; font-size:14px; margin-left:12px;">
            关闭
        </button>
    </div>
</body>
</html>
