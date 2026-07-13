<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">导入操作日志</h2>
            <a href="{{ route('assets.importForm') }}" class="text-sm text-blue-600 hover:text-blue-800">← 返回导入</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        @forelse($logs as $log)
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                        {{ $log->type === 'import' ? 'bg-emerald-100 text-emerald-700' : ($log->type === 'update' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ $log->type === 'import' ? '纯新增' : ($log->type === 'update' ? '纯更新' : '混合') }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                    <span class="text-sm text-gray-400">|</span>
                    <span class="text-sm text-gray-600">{{ $log->operator_name ?? '系统' }}</span>
                </div>
                <div class="text-xs text-gray-400">{{ $log->file_name }}</div>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-3">
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-gray-800">{{ $log->total_rows }}</div>
                    <div class="text-xs text-gray-500">CSV总行数</div>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ $log->inserted }}</div>
                    <div class="text-xs text-emerald-500">新增</div>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ $log->updated }}</div>
                    <div class="text-xs text-amber-500">更新</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-gray-500">{{ $log->skipped }}</div>
                    <div class="text-xs text-gray-500">跳过</div>
                </div>
            </div>

            <!-- 变更详情（只显示有变化的条目） -->
            @if($log->changed_details && count($log->changed_details) > 0)
            <div class="mt-3">
                <details class="group">
                    <summary class="text-sm text-blue-600 cursor-pointer hover:text-blue-800 select-none">
                        <span class="font-medium">查看变更明细</span>
                        <span class="text-gray-400 ml-1">（{{ count($log->changed_details) }} 条）</span>
                    </summary>
                    <div class="mt-2 max-h-80 overflow-y-auto border border-gray-100 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-100 text-xs">
                            <thead class="bg-gray-50/50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-gray-500 w-24">类型</th>
                                    <th class="px-3 py-2 text-left text-gray-500">自有编号</th>
                                    <th class="px-3 py-2 text-left text-gray-500">资产名称</th>
                                    <th class="px-3 py-2 text-left text-gray-500">变更字段</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($log->changed_details as $detail)
                                <tr class="{{ $detail['type'] === 'insert' ? 'bg-emerald-50/30' : 'bg-amber-50/30' }}">
                                    <td class="px-3 py-2">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold {{ $detail['type'] === 'insert' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $detail['type'] === 'insert' ? '新增' : '修改' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-gray-700">{{ $detail['asset_code'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $detail['asset_name'] ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        @if(isset($detail['changes']) && count($detail['changes']) > 0)
                                        <div class="space-y-0.5">
                                            @foreach($detail['changes'] as $field => $chg)
                                            <span class="inline-block bg-white border border-gray-200 rounded px-1.5 py-0.5 mr-1 mb-0.5">
                                                <span class="text-gray-500">{{ \App\Models\Asset::TRACKED_FIELDS[$field] ?? $field }}:</span>
                                                <span class="line-through text-red-400">{{ $chg['old'] ?: '∅' }}</span>
                                                <span class="text-gray-300 mx-0.5">→</span>
                                                <span class="text-green-600 font-medium">{{ $chg['new'] ?: '∅' }}</span>
                                            </span>
                                            @endforeach
                                        </div>
                                        @else
                                        <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
            @endif

            <!-- 关联调拨单 -->
            @if($log->transfer_order_id)
            <div class="mt-2 text-xs text-gray-500">
                已生成调拨单：
                <a href="{{ route('transfers.show', $log->transferOrder) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    {{ $log->transferOrder->order_no ?? '#' . $log->transfer_order_id }}
                </a>
            </div>
            @endif

            <!-- 错误信息 -->
            @if($log->errors && count($log->errors) > 0)
            <div class="mt-2">
                <details class="group">
                    <summary class="text-xs text-red-500 cursor-pointer hover:text-red-700 select-none">
                        <span class="font-medium">查看错误（{{ count($log->errors) }} 条）</span>
                    </summary>
                    <div class="mt-1 bg-red-50 border border-red-200 rounded-xl p-2 max-h-32 overflow-y-auto">
                        @foreach($log->errors as $err)
                        <div class="text-xs text-red-600 py-0.5">{{ $err }}</div>
                        @endforeach
                    </div>
                </details>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-12 text-center">
            <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <p class="text-gray-500">暂无导入操作记录</p>
            <a href="{{ route('assets.importForm') }}" class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-800">去导入</a>
        </div>
        @endforelse

        <!-- 分页 -->
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
