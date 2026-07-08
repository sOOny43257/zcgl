<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>设备借用单 - {{ $borrow->order_no }}</title>
    <style>
        @media print { body { margin:0; } .no-print { display:none !important; } @page { size: A4; margin:0.6in; } }
        body { font-family:"SimSun","Microsoft YaHei",sans-serif; font-size:14px; color:#333; background:#fff; }
        .toolbar { position:fixed; top:0; left:0; right:0; z-index:100; background:#1f2937; color:#fff; padding:12px 20px; display:flex; justify-content:space-between; align-items:center; }
        .toolbar button { padding:8px 20px; border-radius:6px; font-size:14px; cursor:pointer; background:#3b82f6; color:#fff; border:none; }
        .content { margin-top:70px; padding:20px; max-width:700px; margin-left:auto; margin-right:auto; }
        .title { text-align:center; font-size:22px; font-weight:bold; margin-bottom:20px; letter-spacing:5px; }
        .order-no { text-align:right; font-size:13px; margin-bottom:16px; }
        .info-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        .info-table td, .info-table th { border:1px solid #666; padding:8px 12px; font-size:13px; }
        .info-table .label { background:#f5f5f5; width:100px; font-weight:bold; text-align:center; }
        .sign-section { margin-top:50px; display:flex; justify-content:space-between; }
        .sign-item { text-align:center; width:150px; }
        .sign-item .line { border-bottom:1px solid #333; margin-top:40px; }
        .sign-item .hint { font-size:12px; color:#666; margin-top:4px; }
        .stamp-area { margin-top:30px; display:flex; justify-content:flex-end; }
        .stamp-box { border:2px dashed #999; border-radius:8px; width:140px; height:140px; display:flex; align-items:center; justify-content:center; color:#999; font-size:13px; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <div><strong>设备借用单</strong> — {{ $borrow->order_no }}</div>
    <div style="display:flex; gap:10px;">
        <button onclick="window.print()">打印</button>
        <a href="{{ route('borrows.index') }}" style="color:#ccc; font-size:13px; text-decoration:none;">返回列表</a>
    </div>
</div>
<div class="content">
    @php
        $a = $borrow->asset;
        $borrowDept = \App\Models\DepartmentCode::resolveName('department', $borrow->department);
        $assetDept = \App\Models\DepartmentCode::resolveName('department', $a->department ?? '');
        $assetCat = \App\Models\DepartmentCode::resolveName('category', $a->category ?? '');
    @endphp
    <div class="title">设 备 借 用 单</div>
    <div class="order-no">编号：{{ $borrow->order_no }}</div>
    <table class="info-table">
        <tr><td class="label">自有编号</td><td style="font-family:monospace;font-weight:bold;">{{ $a->asset_code ?? '-' }}</td><td class="label">类别</td><td>{{ $assetCat }}</td></tr>
        <tr><td class="label">资产名称</td><td>{{ $a->name ?? '-' }}</td><td class="label">类别</td><td>{{ $assetCat }}</td></tr>
        <tr><td class="label">IP地址</td><td style="font-family:monospace;">{{ $a->ip }}</td><td class="label">MAC地址</td><td style="font-family:monospace;">{{ $a->mac }}</td></tr>
        <tr><td class="label">SN序列号</td><td>{{ $a->sn ?: '-' }}</td><td class="label">品牌/型号</td><td>{{ $a->brand ?: '-' }} / {{ $a->model ?: '-' }}</td></tr>
        <tr><td class="label">借用人</td><td><strong>{{ $borrow->borrower }}</strong></td><td class="label">借用部门</td><td>{{ $borrowDept ?: '-' }}</td></tr>
        <tr><td class="label">借用日期</td><td>{{ $borrow->borrow_date->format('Y年m月d日') }}</td><td class="label">预计归还</td><td>{{ $borrow->expected_return_date ? $borrow->expected_return_date->format('Y年m月d日') : '-' }}</td></tr>
        <tr><td class="label">备注</td><td colspan="3">{{ $borrow->remarks ?: '' }}</td></tr>
    </table>
    <div class="sign-section">
        <div class="sign-item"><div>借用人签字</div><div class="line"></div><div class="hint">日期：____年____月____日</div></div>
        <div class="sign-item"><div>部门负责人签字</div><div class="line"></div><div class="hint">日期：____年____月____日</div></div>
        <div class="sign-item"><div>资产管理员签字</div><div class="line"></div><div class="hint">日期：____年____月____日</div></div>
    </div>
    <div class="stamp-area"><div class="stamp-box">盖章处</div></div>
</div>
</body>
</html>
