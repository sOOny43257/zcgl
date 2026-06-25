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
        .content { margin-top:70px; padding:20px; max-width:800px; margin-left:auto; margin-right:auto; }
        .title { text-align:center; font-size:22px; font-weight:bold; margin-bottom:20px; letter-spacing:4px; }
        .order-no { text-align:right; font-size:12px; margin-bottom:14px; }
        .info-table { width:100%; border-collapse:collapse; margin-bottom:16px; }
        .info-table td, .info-table th { border:1px solid #666; padding:6px 10px; font-size:12px; }
        .info-table .label { background:#f5f5f5; width:100px; font-weight:bold; text-align:center; }
        .cancelled-stamp { position:absolute; top:40%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:60px; color:rgba(255,0,0,0.12); font-weight:bold; pointer-events:none; }
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
    @php $a = $transferOrder->asset; @endphp
    <table class="info-table">
        <tr><td class="label">自有编号</td><td style="font-family:monospace;">{{ $a->asset_code ?? '-' }}</td><td class="label">财务编号</td><td style="font-family:monospace;">{{ $a->financial_code ?: '-' }}</td></tr>
        <tr><td class="label">资产名称</td><td>{{ $a->name ?? '-' }}</td><td class="label">类别</td><td>{{ \App\Models\Asset::translateCat($a->category) }}</td></tr>
        <tr><td class="label">品牌</td><td>{{ $a->brand ?: '-' }}</td><td class="label">规格型号</td><td>{{ $a->model ?: '-' }}</td></tr>
        <tr><td class="label">SN序列号</td><td>{{ $a->sn ?: '-' }}</td><td class="label">房间号</td><td>{{ $a->room ?: '-' }}</td></tr>
        <tr><td class="label">IP地址</td><td style="font-family:monospace;">{{ $a->ip }}</td><td class="label">MAC地址</td><td style="font-family:monospace;">{{ $a->mac }}</td></tr>
        <tr><td class="label">调出部门</td><td>{{ $transferOrder->from_dept ? \App\Models\Asset::translateDept($transferOrder->from_dept) : '-' }}</td><td class="label">调入部门</td><td><strong>{{ $transferOrder->to_dept ? \App\Models\Asset::translateDept($transferOrder->to_dept) : '-' }}</strong></td></tr>
        <tr><td class="label">调出使用人</td><td>{{ $transferOrder->from_user ?: '-' }}</td><td class="label">调入使用人</td><td><strong>{{ $transferOrder->to_user ?: '-' }}</strong></td></tr>
        <tr><td class="label">经办人</td><td>{{ $transferOrder->operator }}</td><td class="label">调拨日期</td><td>{{ $transferOrder->created_at->format('Y年m月d日') }}</td></tr>
        <tr><td class="label">调拨说明</td><td colspan="3">因工作需要，申请将上述资产由{{ $transferOrder->from_dept ? \App\Models\Asset::translateDept($transferOrder->from_dept) : '(空)' }}调拨至{{ $transferOrder->to_dept ? \App\Models\Asset::translateDept($transferOrder->to_dept) : '-' }}使用。</td></tr>
    </table>

    <div style="display:flex; justify-content:space-between; margin-top:26px; padding:0 5px;">
        <div style="text-align:center; width:160px;">
            <div style="font-size:12px;">{{ $transferOrder->from_dept ? \App\Models\Asset::translateDept($transferOrder->from_dept) : '-' }}<span style="margin-left:4px;">（章）</span></div>
            <div style="border-bottom:1px solid #333; margin-top:36px;"></div>
            <div style="font-size:11px; color:#333; margin-top:3px; white-space:nowrap;">调出部门负责人</div>
            <div style="font-size:10px; color:#666; white-space:nowrap;">签字/日期：________年____月____日</div>
        </div>
        <div style="text-align:center; width:160px;">
            <div style="font-size:12px;">{{ $transferOrder->to_dept ? \App\Models\Asset::translateDept($transferOrder->to_dept) : '-' }}<span style="margin-left:4px;">（章）</span></div>
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
