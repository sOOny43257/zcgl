<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">耗材入库</h2>
                <p class="text-sm text-gray-500 mt-0.5">管理耗材入库单</p>
            </div>
            <a href="{{ route('consumable-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 shadow-sm">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                新建入库单
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">单号</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="入库单号" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">状态</label>
            <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">全部</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>草稿</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>已完成</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>已作废</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700">筛选</button>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">入库单号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">入库日期</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">供应商</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">经办人</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">状态</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $o)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><a href="{{ route('consumable-intakes.show', $o) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">{{ $o->order_no }}</a></td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $o->intake_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $o->supplierName() }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $o->operator_name }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $statusMap = ['draft' => ['草稿', 'amber'], 'completed' => ['已完成', 'green'], 'cancelled' => ['已作废', 'gray']]; $s = $statusMap[$o->status] ?? [$o->status, 'gray']; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $s[1] }}-100 text-{{ $s[1] }}-700">{{ $s[0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($o->isDraft())
                            <form method="POST" action="{{ route('consumable-intakes.complete', $o) }}" class="inline" onsubmit="return confirm('确认完成入库？这将更新库存。')">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm mr-2">完成入库</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">暂无入库记录</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-app-layout>
