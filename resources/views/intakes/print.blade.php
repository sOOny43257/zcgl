<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>打印 - 入库单 {{ $intake->order_no ?? '草稿' }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            @page { size: {{ $printTemplate->orientationCss() }}; margin: 15mm; }
        }
        body { font-family: "SimSun", "Microsoft YaHei", sans-serif; font-size: 13px; color: #333; background: #fff; }
        .toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: #1f2937; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
        .toolbar a, .toolbar button { padding: 8px 18px; border-radius: 6px; font-size: 14px; cursor: pointer; border: none; }
        .toolbar .btn { background: #3b82f6; color: #fff; text-decoration: none; }
        .toolbar .ghost { background: transparent; color: #d1d5db; text-decoration: none; border: 1px solid rgba(255,255,255,0.2); }
        .page { margin: 80px auto 40px; padding: 20px; max-width: 1100px; }
        .title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 6px; margin-bottom: 12px; }
        .order-no { text-align: right; font-size: 12px; color: #555; margin-bottom: 14px; }
        .meta-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 24px; margin-bottom: 18px; font-size: 12px; }
        .meta-grid .item span:first-child { color: #666; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #555; padding: 6px 8px; font-size: 11px; word-break: break-all; }
        th { background: #e5e7eb; font-weight: bold; text-align: center; }
        .right { text-align: right; }
        .mono { font-family: monospace; }
        .summary { margin-top: 12px; text-align: right; font-size: 12px; }
        .sign-row { display: flex; justify-content: space-between; gap: 24px; margin-top: 36px; }
        .sign-item { flex: 1; text-align: center; }
        .sign-item .line { border-bottom: 1px solid #333; height: 48px; margin: 24px 0 8px; }
        .sign-item .label { font-size: 12px; color: #333; }
        .print-time { margin-top: 24px; text-align: right; font-size: 11px; color: #777; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <div><strong>{{ data_get($printTemplate->config, 'page.title', '资产入库单') }}</strong> @if($intake->order_no) — {{ $intake->order_no }} @endif</div>
    <div style="display:flex; gap:10px; align-items:center;">
        <a class="ghost" href="{{ route('intakes.show', $intake) }}">返回详情</a>
        @if(auth()->user()->isAdmin())
        <a class="ghost" href="{{ route('print-templates.edit', $printTemplate) }}">编辑模板</a>
        @endif
        <button class="btn" onclick="window.print()">打印</button>
    </div>
</div>

<div class="page">
    <div class="title">{{ data_get($printTemplate->config, 'page.title', '资产入库单') }}</div>
    <div class="order-no">{{ data_get($printTemplate->config, 'page.order_no_prefix', '单号：') }}{{ $intake->order_no ?? '—' }}</div>

    @php
        $metaFields = data_get($printTemplate->config, 'page.meta', []);
        $metaMap = [
            '入库日期' => $intake->intake_date?->format('Y-m-d') ?? '-',
            '供应商' => $intake->supplier ?: '-',
            '采购单号' => $intake->purchase_order_no ?: '-',
            '总金额' => $intake->total_amount ? number_format((float) $intake->total_amount, 2) . ' 元' : '-',
            '经办人' => $intake->operator,
            '验收人' => $intake->approver ?: '-',
            '创建时间' => $intake->created_at->format('Y-m-d H:i'),
            '备注' => $intake->remarks ?: '-',
            '入库说明' => $intake->description ?: '-',
        ];
        $itemSum = collect($items)->sum(fn($it) => (float) ($it['purchase_price'] ?? 0));
    @endphp
    @if(!empty($metaFields))
    <div class="meta-grid">
        @foreach($metaFields as $field)
            @if(isset($metaMap[$field]))
            <div class="item"><span>{{ $field }}：</span><span>{{ $metaMap[$field] }}</span></div>
            @endif
        @endforeach
        <div class="item"><span>资产数量：</span><span>{{ count($items) }} 项</span></div>
    </div>
    @endif

    @php
        $columns = data_get($printTemplate->config, 'table.columns', []);
        $showIndex = (bool) data_get($printTemplate->config, 'table.show_index', true);
        $showTotal = (bool) data_get($printTemplate->config, 'table.show_total', true);
    @endphp
    <table>
        <thead>
            <tr>
                @if($showIndex)
                <th style="width:6%;">序号</th>
                @endif
                @foreach($columns as $col)
                <th>{{ $col['label'] ?? $col['key'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            @php $asset = $createdAssets[$i] ?? null; @endphp
            <tr>
                @if($showIndex)
                <td class="right">{{ $i + 1 }}</td>
                @endif
                @foreach($columns as $col)
                    @php
                        $key = $col['key'] ?? '';
                        $value = '';

                        if (in_array($key, ['asset_code', 'financial_code'], true)) {
                            $value = $asset?->{$key} ?? '';
                        } elseif ($key === 'category') {
                            $value = $catMap[$item['category'] ?? ''] ?? ($item['category'] ?? '');
                        } elseif ($key === 'department') {
                            $value = $deptMap[$item['department'] ?? ''] ?? ($item['department'] ?? '');
                        } elseif ($key === 'purchase_price') {
                            $raw = $item['purchase_price'] ?? null;
                            $value = $raw !== null && $raw !== '' ? number_format((float) $raw, 2) : '';
                        } else {
                            $value = $item[$key] ?? ($asset->{$key} ?? '');
                        }
                    @endphp
                    <td @if($key === 'purchase_price') class="right" @endif>{{ $value }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($showTotal)
    <div class="summary">明细合计：<strong>{{ number_format($itemSum, 2) }}</strong> 元</div>
    @endif

    @php
        $signatures = data_get($printTemplate->config, 'signatures', ['经办人', '验收人']);
    @endphp
    @if(!empty($signatures))
    <div class="sign-row">
        @foreach($signatures as $name)
        <div class="sign-item">
            <div class="label">{{ $name }}签字</div>
            <div class="line"></div>
            <div style="font-size:11px; color:#666;">日期：____年____月____日</div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="print-time">打印时间：{{ date('Y-m-d H:i') }}</div>
</div>
</body>
</html>
