<x-app-layout>
@push('head')
<style>
    .fc-tip { position: relative; cursor: help; }
    .fc-tip:hover::after {
        content: attr(data-full);
        position: absolute; bottom: calc(100% + 4px); left: 50%;
        transform: translateX(-50%);
        background: #1f2937; color: #fff; padding: 3px 8px;
        border-radius: 4px; font-size: 11px; font-family: monospace;
        white-space: nowrap; z-index: 100; pointer-events: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .fc-tip:hover::before {
        content: ''; position: absolute; bottom: 100%; left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent; border-top-color: #1f2937;
        z-index: 100; pointer-events: none;
    }
</style>
<style>
    .pin-l { position: sticky; z-index: 20; background-color: #fff; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.08); }
    .pin-l-th { position: sticky; z-index: 30; background-color: #f9fafb; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.08); }
    tbody tr:hover .pin-l { background-color: #f9fafb; }
    .pin-r { position: sticky; right: 0; z-index: 20; background-color: #fff; box-shadow: -2px 0 5px -2px rgba(0,0,0,0.08); }
    .pin-r-th { position: sticky; right: 0; z-index: 30; background-color: #f9fafb; box-shadow: -2px 0 5px -2px rgba(0,0,0,0.08); }
    tbody tr:hover .pin-r { background-color: #f9fafb; }
</style>
@endpush
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">资产调拨单</h2>
            <div class="flex space-x-2">
                <a href="{{ route('transfers.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">新建调拨</a>
                <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 self-center">← 返回资产列表</a>
            </div>
        </div>
    </x-slot>

    <div x-data="{ expanded: null }" class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
        <div class="p-4 border-b bg-blue-50/50">
            <p class="text-sm text-blue-700">以下记录由系统自动从资产变更日志生成，可作废回滚。<span class="text-gray-500 ml-2">点击<strong>流程单号</strong>可展开查看批量修改明细。</span></p>
        </div>

        {{-- 搜索栏 --}}
        <div class="p-3 border-b bg-gray-50/30">
            <form method="GET" action="{{ route('transfers.index') }}" class="flex items-center gap-3 flex-wrap">
                <div class="flex-1 min-w-[200px] relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="搜索单号、经办人、资产编号..."
                           class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <span class="text-gray-400 text-sm">至</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">搜索</button>
                @if(request('search') || request('date_from') || request('date_to'))
                <a href="{{ route('transfers.index') }}" class="px-4 py-2 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50">重置</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table id="transfer-table" class="min-w-max divide-y divide-gray-100 border-separate border-spacing-0">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="pin-l-th px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap" style="left:0">流程单号</th>
                        <th class="pin-l-th px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap" data-pin="1">自有编号</th>
                        <th class="pin-l-th px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap" data-pin="2">财务编码</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">资产名称</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">流转前部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">流转后部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">流转前使用人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">流转后使用人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">经办人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">日期</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">状态</th>
                        <th class="pin-r-th px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($transfers as $t)
                    <tr class="hover:bg-gray-50/50 cursor-pointer"
                        @if($t->detail_count > 0)
                        @click="expanded = expanded === {{ $t->id }} ? null : {{ $t->id }}"
                        @endif
                    >
                        <td class="pin-l px-3 py-2.5 text-sm font-mono font-medium text-blue-700 hover:text-blue-900" style="left:0">
                            <span class="flex items-center gap-1">
                                @if($t->detail_count > 0)
                                <svg class="h-3 w-3 transition-transform" :class="expanded === {{ $t->id }} ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                @endif
                                {{ $t->order_no ?: '草稿' }}
                                @if($t->detail_count > 1)
                                <span class="text-[10px] text-white bg-blue-500 rounded-full px-1.5 py-0.5 font-sans">{{ $t->detail_count }}</span>
                                @endif
                            </span>
                        </td>
                        <td class="pin-l px-3 py-2.5 text-sm font-mono text-gray-800 whitespace-nowrap" data-pin="1">{{ $t->asset->asset_code ?? '-' }}</td>
                        <td class="pin-l px-3 py-2.5 text-sm font-mono text-gray-500 whitespace-nowrap" data-pin="2">@php $fcl = $t->asset->financial_code ?? ''; $fcd = strlen($fcl) > 14 ? substr($fcl,0,6).'…'.substr($fcl,-8) : ($fcl ?: '-'); @endphp<span class="fc-tip" data-full="{{ $fcl }}">{{ $fcd }}</span></td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap">{{ $t->asset->name ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600 whitespace-nowrap">{{ $t->from_dept ? \App\Models\Asset::translateDept($t->from_dept) : '-' }}</td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap"><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $t->to_dept ? \App\Models\Asset::translateDept($t->to_dept) : '-' }}</span></td>
                        <td class="px-3 py-2.5 text-sm text-gray-600 whitespace-nowrap">{{ $t->from_user ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap"><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ $t->to_user ?: '-' }}</span></td>
                        <td class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap">{{ $t->operator }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-500 whitespace-nowrap">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap" @click.stop>
                            <span class="px-2 py-0.5 rounded text-xs font-medium
                                {{ $t->status === 'draft' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $t->status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $t->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : '' }}">
                                {{ $t->status === 'draft' ? '待提交' : ($t->status === 'cancelled' ? '已作废' : '已生效') }}
                            </span>
                        </td>
                        <td class="pin-r px-3 py-2.5 text-sm space-x-2 whitespace-nowrap" @click.stop>
                            <a href="{{ route('transfers.show', $t) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium">打印</a>
                            <button onclick="openSnapshot({{ $t->id }})" type="button" class="text-cyan-600 hover:text-cyan-800 text-xs font-medium">查看</button>
                            @if($t->status === 'draft')
                            <a href="{{ route('transfers.edit', $t) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">编辑</a>
                            @endif
                            @if($t->status === 'active')
                            <form method="POST" action="{{ route('transfers.cancel') }}" class="inline" onsubmit="return confirm('确定作废此调拨单？将回滚资产变更。')">
                                @csrf
                                <input type="hidden" name="id" value="{{ $t->id }}">
                                <button class="text-orange-500 hover:text-orange-700 text-xs font-medium">作废</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('transfers.destroy', $t) }}" class="inline" onsubmit="return confirm('确定删除此调拨单？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs font-medium">删除</button>
                            </form>
                        </td>
                    </tr>

                    @if($t->detail_count > 0)
                    <tr x-show="expanded === {{ $t->id }}" x-cloak class="bg-blue-50/30">
                        <td colspan="12" class="px-4 py-3">
                            <div class="text-xs font-medium text-gray-600 mb-2">共 <strong>{{ count($t->detail_items) }}</strong> 项资产变更：</div>
                            <table class="min-w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">自有编号</th>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">资产名称</th>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">财务编码</th>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">修改字段</th>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">原值</th>
                                        <th class="px-3 py-2 text-left text-gray-600 whitespace-nowrap">→ 新值</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($t->detail_items as $row)
                                        @php $a = $row['asset']; $cc = count($row['changes']); @endphp
                                        @if($cc === 0)
                                        <tr>
                                            <td class="px-3 py-2 font-mono font-medium whitespace-nowrap">{{ $a->asset_code }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $a->name }}</td>
                                            <td class="px-3 py-2 font-mono text-gray-500 whitespace-nowrap">@php $sfc = $a->financial_code ?: ''; $sfd = strlen($sfc) > 14 ? substr($sfc,0,6).'…'.substr($sfc,-8) : ($sfc ?: '-'); @endphp<span class="fc-tip" data-full="{{ $sfc }}">{{ $sfd }}</span></td>
                                            <td class="px-3 py-2 text-gray-400" colspan="3">无变更</td>
                                        </tr>
                                        @else
                                        @foreach($row['changes'] as $ci => $c)
                                        <tr>
                                            @if($ci === 0)
                                            <td class="px-3 py-2 font-mono font-medium" rowspan="{{ $cc }}">{{ $a->asset_code }}</td>
                                            <td class="px-3 py-2" rowspan="{{ $cc }}">{{ $a->name }}</td>
                                            <td class="px-3 py-2 font-mono text-gray-500" rowspan="{{ $cc }}">{{ $a->financial_code ?: '-' }}</td>
                                            @endif
                                            <td class="px-3 py-2">{{ $c['label'] }}</td>
                                            <td class="px-3 py-2 text-gray-400"><del>{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['old']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['old']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['old']) : $c['old'])) }}</del></td>
                                            <td class="px-3 py-2 font-medium text-blue-700">{{ $c['field'] === 'department' ? \App\Models\Asset::translateDept($c['new']) : ($c['field'] === 'category' ? \App\Models\Asset::translateCat($c['new']) : ($c['field'] === 'status' ? \App\Models\Asset::translateStatus($c['new']) : $c['new'])) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @endif

                    @empty
                    <tr><td colspan="12" class="px-4 py-12 text-center text-gray-500">暂无调拨记录</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span>每页</span>
                <form method="GET" action="{{ route('transfers.index') }}" class="inline" id="perPageForm">
                    @foreach(request()->except('per_page', 'page') as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $iv)<input type="hidden" name="{{ $k }}[]" value="{{ $iv }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <select name="per_page" onchange="document.getElementById('perPageForm').submit()"
                            class="border border-gray-200 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                        @foreach([10, 20, 50] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
                <span>条 · 共 <strong>{{ $transfers->total() }}</strong> 条</span>
            </div>
            {{ $transfers->links() }}
        </div>
    </div>

    {{-- 快照查看弹窗 - 纯 JS 实现 --}}
    <div id="snapshotModal" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
            <div id="snapshotHeader" class="flex items-center justify-between px-6 py-4 border-b bg-gray-50 border-gray-100">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">调拨单详情</h3>
                    <span id="snapOrderNo" class="font-mono text-sm text-gray-500"></span>
                    <span id="snapBadge" style="display:none" class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">已作废</span>
                </div>
                <button onclick="closeSnapshot()" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="snapshotBody" class="overflow-y-auto flex-1 p-6">
                <div class="text-center py-12 text-gray-400">加载中...</div>
            </div>
            <div class="px-6 py-3 border-t bg-gray-50 flex justify-end gap-2">
                <button onclick="closeSnapshot()" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">关闭</button>
            </div>
        </div>
    </div>

    <script>
    function openSnapshot(id) {
        var modal = document.getElementById('snapshotModal');
        var body = document.getElementById('snapshotBody');
        var header = document.getElementById('snapshotHeader');
        var badge = document.getElementById('snapBadge');
        var orderNo = document.getElementById('snapOrderNo');
        modal.style.display = 'flex';
        body.innerHTML = '<div class="text-center py-12 text-gray-400"><svg class="animate-spin h-6 w-6 mx-auto mb-3 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>加载中...</div>';
        var base = (window.APP_URL || '').replace(/\/+$/, '');
        fetch(base + '/transfers/' + id + '/snapshot')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                orderNo.textContent = d.order_no || '';
                if (d.is_cancelled) {
                    header.className = 'flex items-center justify-between px-6 py-4 border-b bg-red-50 border-red-100';
                    badge.style.display = '';
                } else {
                    header.className = 'flex items-center justify-between px-6 py-4 border-b bg-gray-50 border-gray-100';
                    badge.style.display = 'none';
                }
                var html = '';
                html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">';
                html += '<div><div class="text-xs text-gray-400 mb-1">经办人</div><div class="text-sm font-medium text-gray-800">' + esc(d.operator || '-') + '</div></div>';
                html += '<div><div class="text-xs text-gray-400 mb-1">创建时间</div><div class="text-sm text-gray-700">' + esc(d.created_at) + '</div></div>';
                if (d.is_cancelled) {
                    html += '<div><div class="text-xs text-red-400 mb-1">作废时间</div><div class="text-sm font-medium text-red-700">' + esc(d.cancelled_at || '-') + '</div></div>';
                }
                html += '<div><div class="text-xs text-gray-400 mb-1">资产数量</div><div class="text-sm text-gray-700">' + d.itemCount + ' 项</div></div>';
                html += '</div>';
                if (d.reason) {
                    html += '<div class="mb-5 p-3 bg-gray-50 rounded-lg border border-gray-100"><div class="text-xs text-gray-400 mb-1">调拨说明</div><div class="text-sm text-gray-700">' + esc(d.reason) + '</div></div>';
                }
                if (d.is_cancelled) {
                    html += '<div class="mb-5 p-3 bg-red-50 rounded-lg border border-red-100 text-sm text-red-700"><strong>作废提示：</strong>此调拨单已被作废，资产变更已回滚至原值。作废时间：' + esc(d.cancelled_at) + '</div>';
                }
                html += '<div class="text-xs font-medium text-gray-500 mb-2">变更明细</div>';
                html += '<div class="border border-gray-200 rounded-lg overflow-hidden"><table class="min-w-full text-xs"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-gray-500 font-medium">自有编号</th><th class="px-3 py-2 text-left text-gray-500 font-medium">资产名称</th><th class="px-3 py-2 text-left text-gray-500 font-medium">财务编码</th><th class="px-3 py-2 text-left text-gray-500 font-medium">变更内容</th></tr></thead><tbody class="divide-y divide-gray-100">';
                for (var i = 0; i < d.items.length; i++) {
                    var item = d.items[i];
                    html += '<tr class="hover:bg-gray-50">';
                    html += '<td class="px-3 py-2 font-mono text-gray-800 whitespace-nowrap">' + esc(item.asset_code) + '</td>';
                    html += '<td class="px-3 py-2 text-gray-700">' + esc(item.name) + '</td>';
                    html += '<td class="px-3 py-2 font-mono text-gray-500">' + esc(item.financial_code || '-') + '</td>';
                    html += '<td class="px-3 py-2 text-gray-600">';
                    if (item.changes.length === 0) {
                        html += '<span class="text-gray-400">无变更</span>';
                    } else {
                        html += '<div class="space-y-1">';
                        for (var j = 0; j < item.changes.length; j++) {
                            var c = item.changes[j];
                            html += '<div><span class="text-gray-500">' + esc(c.label) + '：</span><del class="text-gray-400">' + esc(c.old) + '</del><span class="text-gray-400 mx-0.5">→</span><span class="font-medium text-blue-700">' + esc(c.new) + '</span></div>';
                        }
                        html += '</div>';
                    }
                    html += '</td></tr>';
                }
                html += '</tbody></table></div>';
                body.innerHTML = html;
            })
            .catch(function(e) {
                body.innerHTML = '<div class="text-center py-12 text-red-400">加载失败: ' + esc(e.message) + '</div>';
            });
    }
    function closeSnapshot() {
        document.getElementById('snapshotModal').style.display = 'none';
    }
    function esc(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }
    document.getElementById('snapshotModal').addEventListener('click', function(e) {
        if (e.target === this) closeSnapshot();
    });
    </script>

<script>
// 计算调拨单表格左固定列偏移
function fixTransferPins() {
    var table = document.getElementById('transfer-table');
    if (!table) return;
    var ths = table.querySelectorAll('thead th');
    if (ths.length < 4) return;
    // th[0]=流程单号(left:0), th[1]=自有编号, th[2]=财务编码
    var off = ths[0].offsetWidth;
    ths[1].style.left = off + 'px';
    off += ths[1].offsetWidth;
    ths[2].style.left = off + 'px';
    // body rows
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 4) return;
        // 跳过展开行(colspan)
        if (cells[0].hasAttribute('colspan')) return;
        var bo = cells[0].offsetWidth;
        cells[1].style.left = bo + 'px';
        bo += cells[1].offsetWidth;
        cells[2].style.left = bo + 'px';
    });
}
window.addEventListener('DOMContentLoaded', fixTransferPins);
window.addEventListener('resize', fixTransferPins);
// Alpine 展开行后重新计算
document.addEventListener('alpine:initialized', function() {
    setTimeout(fixTransferPins, 100);
});
</script>
</x-app-layout>
