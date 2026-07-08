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
                <a href="{{ route('intakes.print', $intake) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    打印
                </a>
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
            @if($intake->description)
            <div class="sm:col-span-4">
                <span class="text-gray-500">入库说明：</span><span class="text-gray-800">{{ $intake->description }}</span>
            </div>
            @endif
        </div>
        <!-- 金额校验提示 -->
        @php
            $itemSum = collect($items)->sum(fn($it) => (float) ($it['purchase_price'] ?? 0));
        @endphp
        @if($intake->total_amount && abs($itemSum - (float)$intake->total_amount) > 0.01)
        <div class="mt-3 p-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            总金额（{{ number_format($intake->total_amount, 2) }}）与明细合计（{{ number_format($itemSum, 2) }}）不一致，差额 {{ number_format(abs($itemSum - (float)$intake->total_amount), 2) }} 元
        </div>
        @endif
    </div>

    <!-- 附件 -->
    @if(!empty($intake->attachments))
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-3">附件</h3>
        <div class="flex flex-wrap gap-3">
            @foreach($intake->attachments as $att)
            @php $ext = strtolower(pathinfo($att, PATHINFO_EXTENSION)); @endphp
            <a href="{{ Storage::url($att) }}" target="_blank"
               class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 hover:border-blue-300">
                @if(in_array($ext, ['jpg','jpeg','png','gif']))
                    <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                @elseif($ext === 'pdf')
                    <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                @else
                    <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                @endif
                <span class="text-gray-700">{{ basename($att) }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

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
                <tfoot class="bg-gray-50 text-sm font-medium">
                    <tr>
                        <td colspan="9" class="px-3 py-2.5 text-right text-gray-500">明细合计</td>
                        <td class="px-3 py-2.5 text-right text-blue-600">{{ number_format($itemSum, 2) }}</td>
                        @if($intake->status === 'active')
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 操作按钮 -->
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
