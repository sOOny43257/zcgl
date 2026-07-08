<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">打印模板管理</h2>
                <p class="text-sm text-gray-500 mt-0.5">统一管理各模块的制式打印模板</p>
            </div>
            <a href="{{ route('system.index') }}" class="px-3 py-2 border border-gray-200 text-gray-600 text-sm rounded-xl hover:bg-gray-50">返回系统管理</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">模块</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">模板名称</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">页面方向</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">启用状态</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">最后更新</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($templates as $template)
                    <tr class="hover:bg-gray-50/50 text-sm">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $template->module }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $template->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $template->orientation === 'landscape' ? '横向' : '纵向' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $template->is_active ? '启用' : '停用' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $template->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('print-templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-800">编辑</a>
                                <form method="POST" action="{{ route('print-templates.reset', $template) }}" onsubmit="return confirm('确定恢复为默认配置？')">
                                    @csrf
                                    <button class="text-red-500 hover:text-red-700">恢复默认</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">暂无模板数据</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
