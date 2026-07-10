<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold text-gray-800">维修管理</h2>
            @if(auth()->user()->isAdmin())
            <div class="flex items-center space-x-2">
                <a href="{{ route('repairs.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    新建维修单
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3">
        <!-- 搜索 + 筛选 -->
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
            <div class="flex gap-2 flex-wrap">
                <div class="flex-1 min-w-[200px] relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="搜索单号、资产编号、名称、故障描述..."
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select name="status" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                    <option value="">全部状态</option>
                    @foreach(\App\Models\Repair::STATUSES as $code => $label)
                        <option value="{{ $code }}" {{ request('status') === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500" placeholder="开始日期">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500" placeholder="结束日期">
                <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700">筛选</button>
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('repairs.index') }}" class="px-3 py-2.5 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50">重置</a>
                @endif
            </div>
        </form>

        <!-- 列表 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-2 border-b bg-gray-50/50">
                <span class="text-xs text-gray-500">共 <strong class="text-blue-600">{{ $repairs->total() }}</strong> 条</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">维修单号</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">资产编号</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">资产名称</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">送修日期</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">故障类别</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">维修方式</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">状态</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">经办人</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($repairs as $repair)
                        <tr class="hover:bg-gray-50/50 text-sm">
                            <td class="px-3 py-2">
                                @if($repair->order_no)
                                    <a href="{{ route('repairs.show', $repair) }}" class="text-blue-600 hover:underline font-mono font-medium">{{ $repair->order_no }}</a>
                                @else
                                    <span class="text-gray-400 text-xs">草稿 #{{ $repair->id }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $repair->asset->asset_code ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if($repair->asset)
                                    <a href="{{ route('assets.show', $repair->asset_id) }}" class="text-blue-600 hover:underline">{{ $repair->asset->name ?: '-' }}</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $repair->repair_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $repair->fault_category ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $repair->repair_method ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-600',
                                        'submitted' => 'bg-blue-100 text-blue-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-600',
                                    ];
                                @endphp
                                <span class="px-1.5 py-0.5 rounded-full text-xs {{ $statusColors[$repair->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $repair->status_name }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $repair->operator ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex gap-2 text-xs whitespace-nowrap">
                                    <a href="{{ route('repairs.show', $repair) }}" class="text-blue-600 hover:underline">查看</a>
                                    @if(auth()->user()->isAdmin() && $repair->status === 'draft')
                                        <a href="{{ route('repairs.edit', $repair) }}" class="text-indigo-600 hover:underline">编辑</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center text-gray-400">暂无维修记录</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($repairs->hasPages())
            <div class="px-4 py-3 border-t flex items-center justify-between">
                <span class="text-xs text-gray-500">共 {{ $repairs->total() }} 条</span>
                {{ $repairs->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
