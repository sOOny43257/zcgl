<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">耗材领用</h2>
                <p class="text-sm text-gray-500 mt-0.5">查看领用记录</p>
            </div>
            <a href="{{ route('consumable-usages.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 shadow-sm">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                新建领用
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">耗材</label>
            <select name="consumable_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-48">
                <option value="">全部</option>
                @foreach($consumables as $c)
                    <option value="{{ $c->id }}" {{ request('consumable_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">部门</label>
            <select name="department_code" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-40">
                <option value="">全部</option>
                @foreach($departments as $d)
                    <option value="{{ $d->code }}" {{ request('department_code') == $d->code ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">起始日期</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">截止日期</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700">筛选</button>
        <a href="{{ route('consumable-usages.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm hover:bg-gray-200">重置</a>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">领用日期</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">耗材名称</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">使用部门</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">数量</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">事由</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作人</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($usages as $u)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $u->usage_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $u->consumable->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $u->departmentName() }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">{{ $u->quantity }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ $u->reason }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $u->operator_name }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">暂无领用记录</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $usages->links() }}</div>
</x-app-layout>
