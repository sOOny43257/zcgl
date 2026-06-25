<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">设备借用记录</h2>
            <div class="flex space-x-2">
                <a href="{{ route('borrows.manage') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                    借用中管理
                </a>
                <a href="{{ route('borrows.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    新增借用
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用单号</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">自有编号</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">资产名称</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用人</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用日期</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">预计归还</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">归还日期</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($borrows as $b)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2.5 text-sm font-mono font-medium">{{ $b->order_no }}</td>
                    <td class="px-3 py-2.5 text-sm font-mono font-medium text-gray-800">{{ $b->asset->asset_code ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-sm">{{ $b->asset->name ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-sm">{{ $b->borrower }}</td>
                    <td class="px-3 py-2.5 text-sm">{{ $b->borrow_date->format('Y-m-d') }}</td>
                    <td class="px-3 py-2.5 text-sm">{{ $b->expected_return_date ? $b->expected_return_date->format('Y-m-d') : '-' }}</td>
                    <td class="px-3 py-2.5 text-sm">{{ $b->return_date ? $b->return_date->format('Y-m-d') : '-' }}</td>
                    <td class="px-3 py-2.5 text-sm">
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $b->return_date ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $b->return_date ? '已归还' : '借用中' }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-sm space-x-2 whitespace-nowrap">
                        <a href="{{ route('borrows.show', $b) }}" target="_blank" class="text-blue-600 hover:text-blue-800">打印</a>
                        @if(!$b->return_date)
                        <form method="POST" action="{{ route('borrows.return', $b) }}" class="inline" onsubmit="return confirm('确认归还？')">
                            @csrf @method('PATCH')
                            <button class="text-green-600 hover:text-green-800">归还</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('borrows.destroy', $b) }}" class="inline" onsubmit="return confirm('确定删除？')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-800">删除</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">暂无借用记录</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t">{{ $borrows->links() }}</div>
    </div>
</x-app-layout>
