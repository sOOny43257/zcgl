<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                维修单详情
                @if($repair->order_no)
                    <span class="text-sm text-gray-400 font-mono font-normal ml-2">{{ $repair->order_no }}</span>
                @else
                    <span class="text-sm text-gray-400 font-normal ml-2">草稿 #{{ $repair->id }}</span>
                @endif
            </h2>
            <a href="{{ route('repairs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <!-- 状态栏 -->
        @php
            $statusColors = [
                'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
                'submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
                'in_progress' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                'completed' => 'bg-green-50 text-green-700 border-green-200',
                'cancelled' => 'bg-red-50 text-red-600 border-red-200',
            ];
        @endphp
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium border {{ $statusColors[$repair->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $repair->status_name }}
                </span>
                @if($repair->status === 'draft')
                    <a href="{{ route('repairs.edit', $repair) }}" class="text-sm text-indigo-600 hover:underline">编辑</a>
                @endif
            </div>
            @if(auth()->user()->isAdmin() && in_array($repair->status, ['submitted', 'in_progress']))
            <div class="flex items-center gap-2">
                <!-- 完成按钮 -->
                <form method="POST" action="{{ route('repairs.complete', $repair) }}" class="inline" id="completeForm">
                    @csrf
                    <input type="hidden" name="actual_completion_date" value="{{ date('Y-m-d') }}">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700"
                            onclick="return confirm('确定标记为已完成？资产状态将恢复为送修前状态。')">
                        标记完成
                    </button>
                </form>
                <!-- 作废按钮 -->
                <form method="POST" action="{{ route('repairs.cancel') }}" class="inline">
                    @csrf
                    <input type="hidden" name="id" value="{{ $repair->id }}">
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50"
                            onclick="return confirm('确定作废此维修单？资产状态将恢复。')">
                        作废
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- 关联资产 -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">关联资产</h3>
            </div>
            <div class="p-6">
                @if($repair->asset)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">自有编号</dt>
                        <dd class="text-sm mt-0.5">
                            <a href="{{ route('assets.show', $repair->asset) }}" class="text-blue-600 hover:underline font-mono font-medium">{{ $repair->asset->asset_code }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">资产名称</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->asset->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">类别</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ \App\Models\Asset::translateCat($repair->asset->category) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">部门</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ \App\Models\Asset::translateDept($repair->asset->department) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">当前状态</dt>
                        <dd class="text-sm mt-0.5">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ \App\Models\Asset::translateStatus($repair->asset->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">使用人</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->asset->user ?: '-' }}</dd>
                    </div>
                </dl>
                @else
                <p class="text-sm text-gray-400">资产已不存在</p>
                @endif
            </div>
        </div>

        <!-- 维修信息 -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">维修信息</h3>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">送修日期</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->repair_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">故障类别</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->fault_category ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">维修方式</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->repair_method ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">维修单位/人员</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->vendor ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">维修费用</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->cost ? number_format($repair->cost, 2) . ' 元' : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">预计完成</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->expected_completion_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">实际完成</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->actual_completion_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">经办人</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->operator ?? '-' }}</dd>
                    </div>
                    @if($repair->fault_description)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-500 uppercase">故障描述</dt>
                        <dd class="text-sm text-gray-900 mt-0.5 whitespace-pre-wrap">{{ $repair->fault_description }}</dd>
                    </div>
                    @endif
                    @if($repair->remarks)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-500 uppercase">备注</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $repair->remarks }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- 附件 -->
        @if($repair->attachments && count($repair->attachments) > 0)
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">附件</h3>
            </div>
            <div class="p-6">
                <div class="space-y-2">
                    @foreach($repair->attachments as $path)
                    <div class="flex items-center py-2 px-3 bg-gray-50 rounded-lg">
                        <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <a href="{{ Storage::url($path) }}" target="_blank" class="text-sm text-blue-600 hover:underline">{{ basename($path) }}</a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- 底部信息 -->
        <div class="text-center text-xs text-gray-400 py-4">
            创建于 {{ $repair->created_at->format('Y-m-d H:i') }} · 最近更新 {{ $repair->updated_at->format('Y-m-d H:i') }}
        </div>
    </div>
</x-app-layout>
