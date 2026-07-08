<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">耗材盘点</h2>
                <p class="text-sm text-gray-500 mt-0.5">盘点管理</p>
            </div>
            <a href="{{ route('consumable-inventories.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 shadow-sm">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                新建盘点单
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">盘点单号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">盘点日期</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">盘点人</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">状态</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($inventories as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><a href="{{ route('consumable-inventories.show', $inv) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">{{ $inv->inventory_no }}</a></td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inv->inventory_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inv->operator_name }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $statusMap = ['draft' => ['草稿', 'amber'], 'completed' => ['已完成', 'green']]; $s = $statusMap[$inv->status] ?? [$inv->status, 'gray']; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $s[1] }}-100 text-{{ $s[1] }}-700">{{ $s[0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($inv->isDraft())
                            <form method="POST" action="{{ route('consumable-inventories.settle', $inv) }}" class="inline" onsubmit="return confirm('确认结算盘点？这将修正库存。')">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm">结算</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">暂无盘点记录</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $inventories->links() }}</div>
</x-app-layout>
