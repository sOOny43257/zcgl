<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">入库单 {{ $order->order_no }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $order->intake_date->format('Y年m月d日') }}</p>
            </div>
            <div class="flex space-x-2">
                @if($order->isDraft())
                    <form method="POST" action="{{ route('consumable-intakes.complete', $order) }}" onsubmit="return confirm('确认完成入库？这将更新库存。')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700">完成入库</button>
                    </form>
                    <form method="POST" action="{{ route('consumable-intakes.cancel', $order) }}" onsubmit="return confirm('确定作废此入库单？')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 rounded-xl text-sm font-medium hover:bg-red-200">作废</button>
                    </form>
                    <a href="{{ route('consumable-intakes.edit', $order) }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">编辑</a>
                @endif
                <a href="{{ route('consumable-intakes.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">返回列表</a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">状态</p>
            @php $statusMap = ['draft' => ['草稿', 'amber'], 'completed' => ['已完成', 'green'], 'cancelled' => ['已作废', 'gray']]; $s = $statusMap[$order->status] ?? [$order->status, 'gray']; @endphp
            <span class="inline-flex items-center px-2.5 py-1 mt-1 rounded-full text-sm font-medium bg-{{ $s[1] }}-100 text-{{ $s[1] }}-700">{{ $s[0] }}</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">供应商</p>
            <p class="text-sm font-medium text-gray-800 mt-1">{{ $order->supplierName() }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">经办人</p>
            <p class="text-sm font-medium text-gray-800 mt-1">{{ $order->operator_name }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">备注</p>
            <p class="text-sm text-gray-600 mt-1">{{ $order->remarks ?: '-' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">入库明细</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">耗材名称</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">规格型号</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">数量</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">单价</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">小计</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">备注</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $item->consumable->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->consumable->spec ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $item->unit_price ? number_format($item->unit_price, 2) : '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $item->subtotal ? number_format($item->subtotal, 2) : '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->remarks ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
