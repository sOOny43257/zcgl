<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">打印模板管理</h2>
                <p class="text-sm text-gray-500 mt-0.5">管理各模块的打印模板配置</p>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">模块</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">模板名称</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">页面方向</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">状态</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($templates as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">
                        {{ $moduleLabels[$t->module] ?? $t->module }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $t->name }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-600">
                        {{ $t->orientation === 'landscape' ? '横向' : '纵向' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($t->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">启用</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">停用</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('print-templates.edit', $t) }}" class="text-blue-600 hover:text-blue-800 text-sm">编辑</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
