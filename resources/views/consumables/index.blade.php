<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">耗材管理</h2>
                <p class="text-sm text-gray-500 mt-0.5">管理耗材基础数据和库存</p>
            </div>
            <div class="flex space-x-2">
                @if(request('low_stock') === '1')
                    <a href="{{ route('consumables.index') }}" class="inline-flex items-center px-4 py-2 bg-amber-100 text-amber-700 rounded-xl text-sm font-medium hover:bg-amber-200">
                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        显示全部
                    </a>
                @else
                    <a href="{{ route('consumables.index', ['low_stock' => '1']) }}" class="inline-flex items-center px-4 py-2 bg-amber-100 text-amber-700 rounded-xl text-sm font-medium hover:bg-amber-200">
                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        库存预警
                    </a>
                @endif
                <a href="{{ route('consumables.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 shadow-sm">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    新增耗材
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <!-- Filters -->
    <form method="GET" class="mb-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">搜索</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="耗材名称/规格" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">分类</label>
            <select name="category_code" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">全部</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->code }}" {{ request('category_code') == $cat->code ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700">筛选</button>
        <a href="{{ route('consumables.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm hover:bg-gray-200">重置</a>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">名称</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">分类</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">规格型号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">单位</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">当前库存</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">安全库存</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">参考单价</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($consumables as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('consumables.show', $c) }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $c->name }}</a>
                        @if($c->isLowStock())
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700">低库存</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $c->categoryName() }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $c->spec ?: '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $c->unitName() }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium {{ $c->isLowStock() ? 'text-red-600' : 'text-gray-800' }}">{{ $c->current_stock }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $c->min_stock }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $c->unit_price ? number_format($c->unit_price, 2) : '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="{{ route('consumables.edit', $c) }}" class="text-blue-600 hover:text-blue-800 text-sm">编辑</a>
                            <form method="POST" action="{{ route('consumables.destroy', $c) }}" onsubmit="return confirm('确定删除该耗材？')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">暂无耗材数据</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $consumables->links() }}</div>
</x-app-layout>
