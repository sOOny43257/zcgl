<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">系统编码管理</h2>
            <div class="flex space-x-2">
                <a href="{{ route('codes.importForm') }}?type={{ $currentType }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">
                    CSV导入
                </a>
                <a href="{{ route('codes.create', ['type' => $currentType]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    添加编码
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm">
        <!-- 类型标签 -->
        <div class="border-b">
            <nav class="flex space-x-1 px-4 pt-3">
                @foreach(['department' => '部门编码', 'category' => '类别编码', 'status' => '状态编码', 'hc_category' => '耗材分类', 'hc_unit' => '耗材单位', 'supplier' => '供应商'] as $key => $label)
                <a href="{{ route('codes.index', ['type' => $key]) }}"
                   class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors {{ $currentType == $key ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
                @endforeach
            </nav>
        </div>
        <div class="p-4 border-b">
            <form method="GET" class="flex gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索编号或名称..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">搜索</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">编号</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">名称</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">创建时间</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($codes as $code)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">{{ $code->code }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $code->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $code->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex space-x-2">
                                <a href="{{ route('codes.edit', $code) }}" class="text-indigo-600 hover:text-indigo-800">编辑</a>
                                <form method="POST" action="{{ route('codes.destroy', $code) }}" onsubmit="return confirm('确定删除？')" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-500">暂无编码数据</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">
            {{ $codes->links() }}
        </div>
    </div>
</x-app-layout>
