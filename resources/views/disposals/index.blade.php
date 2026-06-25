<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">资产报废</h2>
                <p class="text-sm text-gray-500 mt-0.5">报废单管理</p>
            </div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('disposals.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                新建报废单
            </a>
            @endif
        </div>
    </x-slot>

    <!-- 筛选栏 -->
    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">关键词</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="单号/经办人/原因" class="border border-gray-300 rounded-lg py-2 px-3 text-sm w-48">
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
        <a href="{{ route('disposals.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">重置</a>
    </form>

    <!-- 列表 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">报废单号</th>
                        <th class="px-4 py-3 text-left font-medium">报废日期</th>
                        <th class="px-4 py-3 text-left font-medium">处置方式</th>
                        <th class="px-4 py-3 text-center font-medium">资产数量</th>
                        <th class="px-4 py-3 text-left font-medium">经办人</th>
                        <th class="px-4 py-3 text-left font-medium">审批人</th>
                        <th class="px-4 py-3 text-left font-medium">状态</th>
                        <th class="px-4 py-3 text-center font-medium">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($disposals as $disposal)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('disposals.show', $disposal) }}" class="text-blue-600 hover:underline font-mono">{{ $disposal->order_no ?? '（草稿）' }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $disposal->disposal_date?->format('Y-m-d') ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $disposal->disposal_method }}</td>
                        <td class="px-4 py-3 text-center">{{ count($disposal->draft_data['asset_ids'] ?? []) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $disposal->operator }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $disposal->approver ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @if($disposal->status === 'draft')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">草稿</span>
                            @elseif($disposal->status === 'active')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">已生效</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-600">已作废</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('disposals.show', $disposal) }}" class="text-blue-600 hover:underline text-xs">查看</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">暂无报废单</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disposals->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $disposals->links() }}</div>
        @endif
    </div>
</x-app-layout>
