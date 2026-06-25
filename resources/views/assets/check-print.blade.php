<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body, #printContainer {
        font-family: "SimSun", "Microsoft YaHei", sans-serif;
        font-size: 12px;
        color: #333;
        background: #fff;
    }
    .dept-section {
        page-break-before: always;
        padding: 5mm 3mm;
    }
    .dept-section:first-child {
        page-break-before: auto;
    }
    .header-info {
        margin-bottom: 6px;
        text-align: center;
    }
    .header-info h2 {
        font-size: 17px;
        margin-bottom: 3px;
        letter-spacing: 2px;
    }
    .header-info .meta {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 1px;
        padding: 0 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        table-layout: fixed;
    }
    th, td {
        border: 1px solid #333;
        padding: 3px 4px;
        text-align: center;
        font-size: 10px;
        word-break: break-all;
        overflow-wrap: break-word;
    }
    th {
        background: #d9d9d9;
        font-weight: bold;
    }
    thead {
        display: table-header-group;
    }
    tbody {
        display: table-row-group;
    }
    tr {
        page-break-inside: avoid;
    }
    .summary {
        margin-top: 4px;
        font-size: 11px;
        padding: 0 5px;
    }
    .sign-row {
        display: flex;
        justify-content: space-between;
        margin-top: 24px;
        padding: 0 5px;
    }
    .sign-item {
        text-align: center;
        flex: 1;
    }
    .sign-item .line {
        border-bottom: 1px solid #333;
        width: 130px;
        margin: 0 auto 4px;
    }
    .sign-item .stamp {
        border: 1px solid #999;
        border-radius: 4px;
        width: 70px;
        height: 70px;
        margin: 0 auto 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #999;
    }
</style>

@foreach($grouped as $deptCode => $assets)
@php $deptName = $deptMap[$deptCode] ?? $deptCode; @endphp
<div class="dept-section">
    <div class="header-info">
        <h2>资产盘点确认表</h2>
        <div class="meta">
            <span>单位：{{ $unit }}</span>
            <span>部门：<strong>{{ $deptName }}</strong></span>
        </div>
        <div class="meta">
            <span>盘点日期：{{ date('Y年m月d日') }}</span>
            <span>打印时间：{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">序号</th>
                <th style="width:10%;">自有编号</th>
                <th style="width:10%;">财务编号</th>
                <th style="width:11%;">资产名称</th>
                <th style="width:6%;">房间号</th>
                <th style="width:11%;">IP地址</th>
                <th style="width:12%;">MAC地址</th>
                <th style="width:10%;">SN序列号</th>
                <th style="width:6%;">状态</th>
                <th style="width:9%;">备注</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $asset->asset_code ?: '-' }}</td>
                <td>{{ $asset->financial_code ?: '-' }}</td>
                <td>{{ $asset->name ?: '-' }}</td>
                <td>{{ $asset->room ?: '-' }}</td>
                <td style="font-size:9px;">{{ $asset->ip }}</td>
                <td style="font-size:9px;">{{ $asset->mac }}</td>
                <td style="font-size:9px;">{{ $asset->sn ?: '-' }}</td>
                <td style="font-size:9px;">{{ \App\Models\Asset::translateStatus($asset->status) }}</td>
                <td style="font-size:9px;">{{ $asset->remarks ?: '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <span>应盘点数：<strong>{{ $assets->count() }}</strong></span>
        <span style="margin-left:25px;">实际盘点数：________</span>
        <span style="margin-left:25px;">差异数：________</span>
    </div>

    <div class="sign-row">
        <div class="sign-item">
            <div style="margin-bottom:4px;">盘点人签字：</div>
            <div class="line"></div>
            <div style="font-size:9px; margin-top:3px;">日期：____年____月____日</div>
        </div>
        <div class="sign-item">
            <div style="margin-bottom:4px;">部门负责人签字：</div>
            <div class="line"></div>
            <div style="font-size:9px; margin-top:3px;">日期：____年____月____日</div>
        </div>
        <div class="sign-item">
            <div style="margin-bottom:4px;">盖章处</div>
            <div class="stamp">（盖章）</div>
        </div>
    </div>
</div>
@endforeach
