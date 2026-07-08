<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">领用详情</h2>
            <a href="{{ route('print.universal', ['module' => 'consumable_usage', 'id' => $usage->id]) }}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">打印</a>
            <a href="{{ route('consumable-usages.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500">耗材名称</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->consumable->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">使用部门</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->departmentName() }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">领用数量</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->quantity }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">领用日期</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->usage_date->format('Y-m-d') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">操作人</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->operator_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">操作时间</p>
                    <p class="text-sm font-medium text-gray-800">{{ $usage->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-500">领用事由</p>
                <p class="text-sm text-gray-800 mt-1">{{ $usage->reason }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
