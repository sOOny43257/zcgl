<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-semibold text-gray-800">报废单详情</h2>
                @if($disposal->order_no)
                <span class="font-mono text-sm text-gray-500">{{ $disposal->order_no }}</span>
                @endif
                @if($disposal->status === 'draft')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">草稿</span>
                @elseif($disposal->status === 'active')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">已生效</span>
                @else
                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-600">已作废</span>
                @endif
            </div>
            <div class="flex items-center space-x-3">
                @if($disposal->status === 'active')
                <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    打印
                </button>
                @endif
                <a href="{{ route('disposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
            @if($disposal->order_no)
                <a href="{{ route('print.universal', ['module' => 'disposal', 'id' => $disposal->id]) }}" target="_blank" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">打印</a>
            @endif
            </div>
        </div>
    </x-slot>

    <!-- 单据信息 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">报废信息</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">报废日期：</span><span class="text-gray-800">{{ $disposal->disposal_date?->format('Y-m-d') ?? '-' }}</span></div>
            <div><span class="text-gray-500">处置方式：</span><span class="text-gray-800">{{ $disposal->disposal_method }}</span></div>
            <div><span class="text-gray-500">经办人：</span><span class="text-gray-800">{{ $disposal->operator }}</span></div>
            <div><span class="text-gray-500">审批人：</span><span class="text-gray-800">{{ $disposal->approver ?: '-' }}</span></div>
            <div class="sm:col-span-2"><span class="text-gray-500">报废原因：</span><span class="text-gray-800">{{ $disposal->reason ?: '-' }}</span></div>
            <div><span class="text-gray-500">创建时间：</span><span class="text-gray-800">{{ $disposal->created_at->format('Y-m-d H:i') }}</span></div>
            <div><span class="text-gray-500">备注：</span><span class="text-gray-800">{{ $disposal->remarks ?: '-' }}</span></div>
        </div>
    </div>

    <!-- 资产列表 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">报废资产（{{ $assets->count() }} 项）</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-medium">#</th>
                        <th class="px-3 py-2.5 text-left font-medium">资产编号</th>
                        <th class="px-3 py-2.5 text-left font-medium">资产名称</th>
                        <th class="px-3 py-2.5 text-left font-medium">类别</th>
                        <th class="px-3 py-2.5 text-left font-medium">品牌/型号</th>
                        <th class="px-3 py-2.5 text-left font-medium">部门</th>
                        <th class="px-3 py-2.5 text-left font-medium">房间号</th>
                        <th class="px-3 py-2.5 text-left font-medium">使用人</th>
                        <th class="px-3 py-2.5 text-left font-medium">当前状态</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($assets as $i => $asset)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('assets.show', $asset->id) }}" class="font-mono text-xs text-blue-600 hover:underline">{{ $asset->asset_code }}</a>
                        </td>
                        <td class="px-3 py-2.5">{{ $asset->name }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $asset->category_name ?? $asset->category }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $asset->brand }} {{ $asset->model }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $asset->department_name ?? $asset->department }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $asset->room }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $asset->user ?: '-' }}</td>
                        <td class="px-3 py-2.5">
                            <span class="text-gray-600">{{ $asset->status_name ?? $asset->status }}</span>
                            @if(isset($snapshot[$asset->id]) && $snapshot[$asset->id]['status'] !== $asset->status)
                            <span class="text-xs text-gray-400 ml-1">(原: {{ \App\Models\Asset::translateStatus($snapshot[$asset->id]['status']) }})</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 操作按钮 -->
    @if(auth()->user()->isAdmin())
    <div class="mt-4 flex justify-end space-x-3">
        @if($disposal->status === 'draft')
            <a href="{{ route('disposals.edit', $disposal) }}" class="px-4 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">编辑</a>
        @endif
        @if($disposal->status === 'active')
            <form method="POST" action="{{ route('disposals.cancel') }}" onsubmit="return confirm('确定作废此报废单？相关资产状态将恢复为报废前的状态。')">
                @csrf
                <input type="hidden" name="id" value="{{ $disposal->id }}">
                <button type="submit" class="px-4 py-2.5 text-red-600 border border-red-300 rounded-lg text-sm hover:bg-red-50">作废报废单</button>
            </form>
        @endif
    </div>
    @endif
</x-app-layout>
