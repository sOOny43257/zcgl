<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">资产调拨单</h2>
            <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回资产列表</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
        <div class="p-4 border-b bg-blue-50/50">
            <p class="text-sm text-blue-700">以下记录由系统自动从资产变更日志生成，可作废回滚。</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">流程单号</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">自有编号</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">财务编码</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">资产名称</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">流转前部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">流转后部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">流转前使用人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">流转后使用人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">经办人</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">日期</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($transfers as $t)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-3 py-2.5 text-sm font-mono font-medium">{{ $t->order_no }}</td>
                        <td class="px-3 py-2.5 text-sm font-mono text-gray-800">{{ $t->asset->asset_code ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm font-mono text-gray-500">{{ $t->asset->financial_code ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm">{{ $t->asset->name ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ $t->from_dept ? \App\Models\Asset::translateDept($t->from_dept) : '-' }}</td>
                        <td class="px-3 py-2.5 text-sm"><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $t->to_dept ? \App\Models\Asset::translateDept($t->to_dept) : '-' }}</span></td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ $t->from_user ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm"><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ $t->to_user ?: '-' }}</span></td>
                        <td class="px-3 py-2.5 text-sm text-gray-700">{{ $t->operator }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-500 whitespace-nowrap">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5 text-sm">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $t->is_cancelled ? 'bg-gray-100 text-gray-600' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $t->is_cancelled ? '已作废' : '有效' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-sm space-x-2 whitespace-nowrap">
                            <a href="{{ route('transfers.show', $t) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium">打印</a>
                            @if(!$t->is_cancelled)
                            <form method="POST" action="{{ route('transfers.cancel') }}" class="inline" onsubmit="return confirm('确定作废此调拨单？将回滚资产变更。')">
                                @csrf
                                <input type="hidden" name="id" value="{{ $t->id }}">
                                <button class="text-red-500 hover:text-red-700 text-xs font-medium">作废</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-4 py-12 text-center text-gray-500">暂无调拨记录</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $transfers->links() }}</div>
    </div>
</x-app-layout>
