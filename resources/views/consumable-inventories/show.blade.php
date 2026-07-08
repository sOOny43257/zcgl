<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">盘点单 {{ $inventory->inventory_no }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $inventory->inventory_date->format('Y年m月d日') }}</p>
            </div>
            <div class="flex space-x-2">
                @if($inventory->isDraft())
                    <form method="POST" action="{{ route('consumable-inventories.settle', $inventory) }}" onsubmit="return confirm('确认结算盘点？这将修正库存。')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700">结算盘点</button>
                    </form>
                @endif
                <a href="{{ route('print.universal', ['module' => 'consumable_inventory', 'id' => $inventory->id]) }}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">打印</a>
                <a href="{{ route('consumable-inventories.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">返回列表</a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">状态</p>
            @php $statusMap = ['draft' => ['草稿', 'amber'], 'completed' => ['已完成', 'green']]; $s = $statusMap[$inventory->status] ?? [$inventory->status, 'gray']; @endphp
            <span class="inline-flex items-center px-2.5 py-1 mt-1 rounded-full text-sm font-medium bg-{{ $s[1] }}-100 text-{{ $s[1] }}-700">{{ $s[0] }}</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">盘点人</p>
            <p class="text-sm font-medium text-gray-800 mt-1">{{ $inventory->operator_name }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">备注</p>
            <p class="text-sm text-gray-600 mt-1">{{ $inventory->remarks ?: '-' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">盘点明细</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">耗材名称</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">账面库存</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">实际数量</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">差异</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">差异原因</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">已调整</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($inventory->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $item->consumable->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $item->book_quantity }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">{{ $item->actual_quantity }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium {{ $item->difference > 0 ? 'text-green-600' : ($item->difference < 0 ? 'text-red-600' : 'text-gray-600') }}">
                        {{ $item->difference > 0 ? '+' : '' }}{{ $item->difference }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->reason ?: '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($item->adjusted)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">是</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">否</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
