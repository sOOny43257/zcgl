<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-semibold text-gray-800">入库单详情</h2>
                @if($intake->order_no)
                <span class="font-mono text-sm text-gray-500">{{ $intake->order_no }}</span>
                @endif
                @if($intake->status === 'draft')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">草稿</span>
                @elseif($intake->status === 'active')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">已生效</span>
                @else
                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-600">已作废</span>
                @endif
            </div>
            <div class="flex items-center space-x-3">
                @if($intake->status === 'active')
                <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    打印
                </button>
                @endif
                <a href="{{ route('intakes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
            </div>
        </div>
    </x-slot>

    <!-- 单据信息 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">单据信息</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">入库日期：</span><span class="text-gray-800">{{ $intake->intake_date?->format('Y-m-d') ?? '-' }}</span></div>
            <div><span class="text-gray-500">供应商：</span><span class="text-gray-800">{{ $intake->supplier ?: '-' }}</span></div>
            <div><span class="text-gray-500">采购单号：</span><span class="text-gray-800">{{ $intake->purchase_order_no ?: '-' }}</span></div>
            <div><span class="text-gray-500">总金额：</span><span class="text-gray-800">{{ $intake->total_amount ? number_format($intake->total_amount, 2) . ' 元' : '-' }}</span></div>
            <div><span class="text-gray-500">经办人：</span><span class="text-gray-800">{{ $intake->operator }}</span></div>
            <div><span class="text-gray-500">验收人：</span><span class="text-gray-800">{{ $intake->approver ?: '-' }}</span></div>
            <div><span class="text-gray-500">创建时间：</span><span class="text-gray-800">{{ $intake->created_at->format('Y-m-d H:i') }}</span></div>
            <div><span class="text-gray-500">备注：</span><span class="text-gray-800">{{ $intake->remarks ?: '-' }}</span></div>
        </div>
    </div>

    <!-- 资产明细 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">资产明细（{{ count($items) }} 项）</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-medium">#</th>
                        <th class="px-3 py-2.5 text-left font-medium">资产名称</th>
                        <th class="px-3 py-2.5 text-left font-medium">类别</th>
                        <th class="px-3 py-2.5 text-left font-medium">品牌</th>
                        <th class="px-3 py-2.5 text-left font-medium">规格型号</th>
                        <th class="px-3 py-2.5 text-left font-medium">SN序列号</th>
                        <th class="px-3 py-2.5 text-left font-medium">部门</th>
                        <th class="px-3 py-2.5 text-left font-medium">房间号</th>
                        <th class="px-3 py-2.5 text-left font-medium">使用人</th>
                        <th class="px-3 py-2.5 text-right font-medium">单价</th>
                        @if($intake->status === 'active')
                        <th class="px-3 py-2.5 text-left font-medium">资产编号</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $i => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2.5">{{ $item['name'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $catMap[$item['category'] ?? ''] ?? ($item['category'] ?? '') }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $item['brand'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $item['model'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-gray-600 font-mono text-xs">{{ $item['sn'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $deptMap[$item['department'] ?? ''] ?? ($item['department'] ?? '') }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $item['room'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $item['user'] ?? '' }}</td>
                        <td class="px-3 py-2.5 text-right">{{ !empty($item['purchase_price']) ? number_format($item['purchase_price'], 2) : '' }}</td>
                        @if($intake->status === 'active')
                        <td class="px-3 py-2.5 font-mono text-xs text-blue-600">
                            @if(isset($createdAssets[$i]))
                            <a href="{{ route('assets.show', $createdAssets[$i]->id) }}">{{ $createdAssets[$i]->asset_code }}</a>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 操作按钮（草稿可编辑/作废） -->
    @if(auth()->user()->isAdmin())
    <div class="mt-4 flex justify-end space-x-3">
        @if($intake->status === 'draft')
            <a href="{{ route('intakes.edit', $intake) }}" class="px-4 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">编辑</a>
        @endif
        @if($intake->status === 'active')
            <form method="POST" action="{{ route('intakes.cancel') }}" onsubmit="return confirm('确定作废此入库单？已入库的资产不受影响。')">
                @csrf
                <input type="hidden" name="id" value="{{ $intake->id }}">
                <button type="submit" class="px-4 py-2.5 text-red-600 border border-red-300 rounded-lg text-sm hover:bg-red-50">作废入库单</button>
            </form>
        @endif
    </div>
    @endif
</x-app-layout>
