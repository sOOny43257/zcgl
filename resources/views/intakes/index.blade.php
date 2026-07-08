<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">资产入库</h2>
                <p class="text-sm text-gray-500 mt-0.5">入库单管理</p>
            </div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                新建入库单
            </a>
            @endif
        </div>
    </x-slot>

    <!-- 筛选栏 -->
    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">关键词</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="单号/供应商/经办人" class="border border-gray-300 rounded-lg py-2 px-3 text-sm w-48">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">状态</label>
            <select name="status" class="border border-gray-300 rounded-lg py-2 px-3 text-sm">
                <option value="">全部</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>草稿</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>已生效</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>已作废</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">开始日期</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg py-2 px-3 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">截止日期</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg py-2 px-3 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">搜索</button>
        <a href="{{ route('intakes.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">重置</a>
    </form>

    <!-- 列表 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- 计数 -->
        <div class="px-4 py-2 border-b bg-gray-50/50 flex items-center justify-between">
            <span class="text-xs text-gray-500">共 <strong class="text-blue-600">{{ $intakes->total() }}</strong> 条入库单</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-medium">#</th>
                        <th class="px-3 py-2.5 text-left font-medium">入库单号</th>
                        <th class="px-3 py-2.5 text-left font-medium">入库日期</th>
                        <th class="px-3 py-2.5 text-left font-medium">供应商</th>
                        <th class="px-3 py-2.5 text-left font-medium">采购单号</th>
                        <th class="px-3 py-2.5 text-center font-medium">资产数量</th>
                        <th class="px-3 py-2.5 text-right font-medium">总金额</th>
                        <th class="px-3 py-2.5 text-left font-medium">经办人</th>
                        <th class="px-3 py-2.5 text-left font-medium">验收人</th>
                        <th class="px-3 py-2.5 text-left font-medium">状态</th>
                        <th class="px-3 py-2.5 text-left font-medium">创建时间</th>
                        <th class="px-3 py-2.5 text-center font-medium">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($intakes as $idx => $intake)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-3 py-2.5 text-xs text-gray-400">{{ ($intakes->currentPage() - 1) * $intakes->perPage() + $idx + 1 }}</td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('intakes.show', $intake) }}" class="text-blue-600 hover:underline font-mono font-medium">{{ $intake->order_no ?? '（草稿）' }}</a>
                        </td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $intake->intake_date?->format('Y-m-d') ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $intake->supplier ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono text-xs">{{ $intake->purchase_order_no ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-center">
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700 font-medium">{{ count($intake->draft_data['items'] ?? []) }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-right font-mono">{{ $intake->total_amount ? number_format($intake->total_amount, 2) : '-' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $intake->operator }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $intake->approver ?: '-' }}</td>
                        <td class="px-3 py-2.5">
                            @if($intake->status === 'draft')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">草稿</span>
                            @elseif($intake->status === 'active')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">已生效</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-600">已作废</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-gray-400 text-xs">{{ $intake->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2 text-xs whitespace-nowrap">
                                <a href="{{ route('intakes.show', $intake) }}" class="text-blue-600 hover:underline">查看</a>
                                @if(auth()->user()->isAdmin() && $intake->status === 'draft')
                                    <a href="{{ route('intakes.edit', $intake) }}" class="text-indigo-600 hover:underline">编辑</a>
                                @endif
                                @if($intake->status === 'active')
                                    <a href="{{ route('intakes.print', $intake) }}" target="_blank" class="text-green-600 hover:underline">打印</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="px-4 py-16 text-center text-gray-400">暂无入库单</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($intakes->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $intakes->links() }}</div>
        @endif
    </div>
</x-app-layout>
