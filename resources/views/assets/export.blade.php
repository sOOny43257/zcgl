<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>资产盘点确认表 - {{ config('app.name') }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            @page { size: A4 landscape; margin: 0.4in; }
        }
        body {
            font-family: "SimSun", "Microsoft YaHei", sans-serif;
            font-size: 13px;
            color: #333;
            background: #fff;
        }
        .toolbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: #1f2937; color: white; padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .toolbar button, .toolbar select { padding: 8px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .toolbar button { background: #3b82f6; color: white; border: none; }
        .toolbar select { background: white; color: #333; border: 1px solid #ccc; }
        .toolbar button:hover { background: #2563eb; }
        .content { margin-top: 60px; }
        .dept-section { margin-bottom: 20px; page-break-after: always; }
        .dept-section:last-child { page-break-after: auto; }
        .header-info { margin-bottom: 16px; }
        .header-info h2 { text-align: center; font-size: 20px; margin-bottom: 8px; }
        .header-info .meta { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #666; padding: 6px 8px; text-align: center; font-size: 12px; }
        th { background: #f0f0f0; font-weight: bold; }
        td { background: #fff; }
        .sign-area { margin-top: 20px; }
        .sign-row { display: flex; justify-content: space-between; margin-top: 36px; }
        .sign-item { text-align: center; }
        .sign-item .line { border-bottom: 1px solid #333; width: 150px; margin-bottom: 4px; }
    </style>
</head>
<body>

    <!-- 工具栏（打印时隐藏） -->
    <div class="toolbar no-print">
        <div>
            <strong>资产盘点确认表</strong>
            @if(request('department'))
                - {{ request('department') }}
            @endif
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <select id="deptFilter" onchange="location.href='?department='+this.value">
                <option value="">所有部门</option>
                @foreach($grouped->keys() as $dept)
                    <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ \App\Models\Asset::translateDept($dept) }}</option>
                @endforeach
            </select>
            <button onclick="window.print()">打印</button>
            <a href="{{ route('assets.index') }}" style="color:#ccc; font-size:13px; text-decoration:none;">返回资产列表</a>
        </div>
    </div>

    <!-- 盘点表内容 -->
    <div class="content">
        @forelse($grouped as $deptName => $assets)
        <div class="dept-section">
            <!-- 页头信息 -->
            <div class="header-info">
                <h2>资产盘点确认表</h2>
                <div class="meta">
                    <span>单位：{{ config('app.name') }}</span>
                    <span>部门：<strong>{{ $deptName }}</strong></span>
                </div>
                <div class="meta">
                    <span>盘点日期：{{ date('Y年m月d日') }}</span>
                    <span>打印时间：{{ date('Y-m-d H:i') }}</span>
                </div>
                <div class="meta">
                    <span>盘点人：_____________</span>
                    <span>复核人：_____________</span>
                </div>
            </div>

            <!-- 资产清单表格 -->
            <table>
                <thead>
                    <tr>
                        <th width="30">序号</th>
                        <th width="100">资产名称</th>
                        <th width="50">房间号</th>
                        <th width="90">IP地址</th>
                        <th width="100">MAC地址</th>
                        <th width="90">SN序列号</th>
                        <th width="40">状态</th>
                        <th width="80">使用人签字</th>
                        <th width="60">盘点确认</th>
                        <th width="60">备注</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $index => $asset)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $asset->name ?: '-' }}</td>
                        <td>{{ $asset->room ?: '-' }}</td>
                        <td style="font-size:10px;">{{ $asset->ip }}</td>
                        <td style="font-size:10px;">{{ $asset->mac }}</td>
                        <td style="font-size:10px;">{{ $asset->sn ?: '-' }}</td>
                        <td style="font-size:11px;">{{ \App\Models\Asset::translateStatus($asset->status) }}</td>
                        <td>{{ $asset->user ?: '______' }}</td>
                        <td>□</td>
                        <td style="font-size:10px;">{{ $asset->remarks ?: '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 汇总 -->
            <div style="margin-top:8px; font-size:13px;">
                <span>应盘点数：<strong>{{ $assets->count() }}</strong></span>
                <span style="margin-left:30px;">实际盘点数：________</span>
                <span style="margin-left:30px;">差异数：________</span>
            </div>

            <!-- 签字区 -->
            <div class="sign-row">
                <div class="sign-item">
                    <div style="margin-bottom:4px;">盘点人签字：</div>
                    <div class="line"></div>
                    <div style="font-size:11px; margin-top:4px;">日期：____年____月____日</div>
                </div>
                <div class="sign-item">
                    <div style="margin-bottom:4px;">部门负责人签字：</div>
                    <div class="line"></div>
                    <div style="font-size:11px; margin-top:4px;">日期：____年____月____日</div>
                </div>
                <div class="sign-item">
                    <div style="margin-bottom:4px;">资产管理处签字：</div>
                    <div class="line"></div>
                    <div style="font-size:11px; margin-top:4px;">日期：____年____月____日</div>
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:80px 0; color:#999;">
            <p>暂无可导出的资产数据</p>
        </div>
        @endforelse
    </div>

</body>
</html>
