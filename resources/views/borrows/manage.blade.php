<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">借用中资产管理</h2>
            <div class="flex space-x-2">
                <a href="{{ route('borrows.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    新增借用
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{ selected: [], selectAll: false }">
        @if($activeBorrows->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-500">
            <svg class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-lg font-medium">当前没有借用中的设备</p>
            <p class="text-sm mt-1">所有借用设备均已归还</p>
        </div>
        @else
        <!-- 批量操作栏 -->
        <div class="bg-white rounded-xl shadow-sm mb-4 p-3 flex items-center" x-show="selected.length > 0" x-cloak>
            <span class="text-sm text-gray-600 mr-4">已选 <span class="font-bold text-blue-600" x-text="selected.length"></span> 条</span>
            <form method="POST" action="{{ route('borrows.batchReturn') }}" x-ref="batchReturnForm">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="borrow_ids[]" :value="id">
                </template>
                <button type="button" @click="if(confirm('确认归还选中的 ' + selected.length + ' 台设备？')) $refs.batchReturnForm.submit()"
                        class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    批量归还
                </button>
            </form>
        </div>

        <!-- 借用中设备表格 -->
        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" x-model="selectAll" @change="selected = selectAll ? {{ json_encode($activeBorrows->pluck('id')) }} : []" class="rounded">
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">资产名称</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用日期</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">预计归还</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">借用单号</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">归还前状态</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($activeBorrows as $borrow)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" value="{{ $borrow->id }}" x-model="selected" class="rounded">
                        </td>
                        <td class="px-3 py-2.5 text-sm font-mono font-bold text-gray-800">{{ $borrow->asset->asset_code ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-700">{{ $borrow->asset->name ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm font-medium text-gray-800">{{ $borrow->borrower }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ $borrow->department ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ $borrow->borrow_date->format('Y-m-d') }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ $borrow->expected_return_date ? $borrow->expected_return_date->format('Y-m-d') : '-' }}</td>
                        <td class="px-3 py-2.5 text-sm font-mono text-gray-600">{{ $borrow->order_no }}</td>
                        <td class="px-3 py-2.5 text-sm">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                {{ $borrow->previous_status === 'ZY' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ \App\Models\Asset::translateStatus($borrow->previous_status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-sm space-x-2 whitespace-nowrap">
                            <a href="{{ route('borrows.show', $borrow) }}" target="_blank" class="text-blue-600 hover:text-blue-800">打印</a>
                            <form method="POST" action="{{ route('borrows.return', $borrow) }}" class="inline" onsubmit="return confirm('确认归还设备 {{ $borrow->asset->asset_code }}？')">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:text-green-800 font-medium">归还</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-app-layout>
