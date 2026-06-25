<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>资产调拨单 - {{ $transferOrder->order_no }}</title>
    <style>
        @media print { body { margin:0; } .no-print { display:none !important; } @page { size: A4; margin:0.5in; } }
        body { font-family:"SimSun","Microsoft YaHei",sans-serif; font-size:13px; color:#333; background:#fff; }
        .toolbar { position:fixed; top:0; left:0; right:0; z-index:100; background:#1f2937; color:#fff; padding:12px 20px; display:flex; justify-content:space-between; align-items:center; }
        .toolbar button { padding:8px 20px; border-radius:6px; font-size:14px; cursor:pointer; background:#3b82f6; color:#fff; border:none; }
        .content { margin-top:70px; padding:20px; max-width:900px; margin-left:auto; margin-right:auto; }
        .title { text-align:center; font-size:22px; font-weight:bold; margin-bottom:20px; letter-spacing:4px; }
        .order-no { text-align:right; font-size:12px; margin-bottom:14px; }
        .info-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        .info-table td, .info-table th { border:1px solid #666; padding:6px 8px; font-size:11px; }
        .info-table .head { background:#f5f5f5; font-weight:bold; text-align:center; }
        .info-table .mono { font-family:monospace; }
        .cancelled-stamp { position:absolute; top:40%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:60px; color:rgba(255,0,0,0.12); font-weight:bold; pointer-events:none; }
        .summary { font-size:12px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <div><strong>资产调拨单</strong> — {{ $transferOrder->order_no }} @if($transferOrder->is_cancelled) <span style="color:#fbbf24;">(已作废)</span> @endif</div>
    <div style="display:flex; gap:10px;">
        <button onclick="window.print()">打印</button>
        <a href="{{ route('transfers.index') }}" style="color:#ccc; font-size:13px; text-decoration:none;">返回列表</a>
    </div>
</div>
<div class="content" style="position:relative;">
    @if($transferOrder->is_cancelled)<div class="cancelled-stamp">已 作 废</div>@endif
    <div class="title">资 产 调 拨 单</div>
    <div class="order-no">编号：{{ $transferOrder->order_no }}</div>
    <div class="summary">经办人：{{ $transferOrder->operator }} &nbsp;&nbsp; 日期：{{ $transferOrder->created_at->format('Y年m月d日') }} &nbsp;&nbsp; 共 <strong>{{ count($items) }}</strong> 项资产</div>

    @if(count($items) === 1)
        {{-- 单项资产：显示详细信息 --}}
        @php $row = $items[0]; $a = $row['asset']; @endphp
        <table class="info-table">
            <tr><td class="head" width="90">自有编号</td><td class="mono" width="180">{{ $a->asset_code ?? '-' }}</td><td class="head" width="90">财务编号</td><td class="mono" width="180">{{ $a->financial_code ?: '-' }}</td></tr>
            <tr><td class="head">资产名称</td><td>{{ $a->name ?? '-' }}</td><td class="head">类别</td><td>{{ \App\Models\Asset::translateCat($a->category) }}</td></tr>
            <tr><td class="head">品牌</td><td>{{ $a->brand ?: '-' }}</td><td class="head">规格型号</td><td>{{ $a->model ?: '-' }}</td></tr>
            <tr><td class="head">SN序列号</td><td>{{ $a->sn ?: '-' }}</td><td class="head">房间号</td><td>{{ $a->room ?: '-' }}</td></tr>
            <tr><td class="head">IP地址</td><td class="mono">{{ $a->ip ?: '-' }}</td><td class="head">MAC地址</td><td class="mono">{{ $a->mac ?: '-' }}</td></tr>
            @if(count($row['changes']) > 0)
            <tr><td class="head" colspan="4" style="text-align:left;">变更明细</td></tr>
            @foreach($row['changes'] as $c)
            <tr>
                <td class="head">{{ $c['label'] }}</td>
                <td><del style="color:#999;">{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['old']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['old']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['old']) : $c['old'])) }}</del></td>
                <td class="head">→</td>
                <td><strong>{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['new']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['new']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['new']) : $c['new'])) }}</strong></td>
            </tr>
            @endforeach
            @endif
            <tr><td class="head">调拨说明</td><td colspan="3">{{ $transferOrder->reason ?: '因工作需要调拨资产' }}</td></tr>
        </table>
    @else
        {{-- 多项资产：列表 + 变更 --}}
        <table class="info-table">
            <thead><tr><th class="head">自有编号</th><th class="head">资产名称</th><th class="head">财务编码</th><th class="head">修改字段</th><th class="head">原值</th><th class="head">→ 新值</th></tr></thead>
            <tbody>
            @foreach($items as $row)
            @php $a = $row['asset']; $changeCount = count($row['changes']); @endphp
            @if($changeCount === 0)
            <tr><td class="mono">{{ $a->asset_code }}</td><td>{{ $a->name }}</td><td class="mono">{{ $a->financial_code ?: '-' }}</td><td colspan="3" style="color:#999;">无变更</td></tr>
            @else
            @foreach($row['changes'] as $ci => $c)
            <tr>
                @if($ci === 0)
                <td class="mono" rowspan="{{ $changeCount }}">{{ $a->asset_code }}</td>
                <td rowspan="{{ $changeCount }}">{{ $a->name }}</td>
                <td class="mono" rowspan="{{ $changeCount }}">{{ $a->financial_code ?: '-' }}</td>
                @endif
                <td>{{ $c['label'] }}</td>
                <td style="color:#999;"><del>{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['old']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['old']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['old']) : $c['old'])) }}</del></td>
                <td><strong>{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['new']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['new']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['new']) : $c['new'])) }}</strong></td>
            </tr>
            @endforeach
            @endif
            @endforeach
            </tbody>
        </table>
        <table class="info-table">
            <tr><td class="head" width="90">调拨说明</td><td>{{ $transferOrder->reason ?: '因工作需要调拨资产' }}</td></tr>
        </table>
    @endif

    {{-- 签名区 --}}
    <div style="display:flex; justify-content:space-between; margin-top:26px; padding:0 5px;">
        <div style="text-align:center; width:160px;">
            <div style="font-size:12px;">调出部门<span style="margin-left:4px;">（章）</span></div>
            <div style="border-bottom:1px solid #333; margin-top:36px;"></div>
            <div style="font-size:11px; color:#333; margin-top:3px; white-space:nowrap;">调出部门负责人</div>
            <div style="font-size:10px; color:#666; white-space:nowrap;">签字/日期：________年____月____日</div>
        </div>
        <div style="text-align:center; width:160px;">
            <div style="font-size:12px;">调入部门<span style="margin-left:4px;">（章）</span></div>
            <div style="border-bottom:1px solid #333; margin-top:36px;"></div>
            <div style="font-size:11px; color:#333; margin-top:3px; white-space:nowrap;">调入部门负责人</div>
            <div style="font-size:10px; color:#666; white-space:nowrap;">签字/日期：________年____月____日</div>
        </div>
        <div style="text-align:center; width:160px;">
            <div style="height:14px;"></div>
            <div style="border-bottom:1px solid #333; margin-top:36px;"></div>
            <div style="font-size:11px; color:#333; margin-top:3px; white-space:nowrap;">经办人</div>
            <div style="font-size:10px; color:#666; white-space:nowrap;">签字/日期：________年____月____日</div>
        </div>
    </div>
</div>
</body>
</html>
