<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $consumable->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $consumable->categoryName() }} / {{ $consumable->spec ?: '无规格' }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('consumables.edit', $consumable) }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">编辑</a>
                <a href="{{ route('consumables.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">返回列表</a>
            </div>
        </div>
    </x-slot>

    <!-- Info Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">当前库存</p>
            <p class="text-2xl font-bold {{ $consumable->isLowStock() ? 'text-red-600' : 'text-gray-800' }}">{{ $consumable->current_stock }}</p>
            <p class="text-xs text-gray-400">{{ $consumable->unitName() }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">安全库存</p>
            <p class="text-2xl font-bold text-gray-800">{{ $consumable->min_stock }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">参考单价</p>
            <p class="text-2xl font-bold text-gray-800">{{ $consumable->unit_price ? '¥' . number_format($consumable->unit_price, 2) : '-' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">默认供应商</p>
            <p class="text-lg font-medium text-gray-800">{{ $consumable->supplierName() }}</p>
        </div>
    </div>

    @if($consumable->remarks)
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-2xl p-4 text-sm text-yellow-800">
        {{ $consumable->remarks }}
    </div>
    @endif

    <!-- Logs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">操作记录</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">时间</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作人</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">详情</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">库存变化</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->user_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm">
                        @php
                            $actionLabels = [
                                'intake_complete' => ['入库完成', 'green'],
                                'usage' => ['领用出库', 'blue'],
                                'inventory_adjust' => ['盘点调整', 'amber'],
                                'update' => ['信息变更', 'gray'],
                                'cancel' => ['作废', 'red'],
                                'delete' => ['删除', 'red'],
                                'inventory_surplus' => ['盘盈', 'green'],
                                'inventory_deficit' => ['盘亏', 'red'],
                            ];
                            $label = $actionLabels[$log->action] ?? [$log->action, 'gray'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $label[1] }}-100 text-{{ $label[1] }}-700">{{ $label[0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->description ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right">
                        @if($log->old_stock !== null && $log->new_stock !== null)
                            {{ $log->old_stock }} → {{ $log->new_stock }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">暂无操作记录</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
