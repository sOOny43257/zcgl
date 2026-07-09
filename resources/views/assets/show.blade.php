<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">资产详情</h2>
            <div class="flex space-x-2">
                <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('assets.edit', $asset) }}" class="text-sm text-indigo-600 hover:text-indigo-800">编辑</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <!-- 基本信息卡片 -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">{{ $asset->name ?: '未命名资产' }}</h3>
                <p class="text-sm text-gray-500 mt-1">创建于 {{ $asset->created_at->format('Y-m-d H:i') }} · 最近更新 {{ $asset->updated_at->format('Y-m-d H:i') }}</p>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">自有编号</dt>
                        <dd class="text-sm text-gray-900 mt-0.5 font-mono font-medium">{{ $asset->asset_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">财务编码</dt>
                        <dd class="text-sm text-gray-900 mt-0.5 font-mono">{{ $asset->financial_code ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">资产名称</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">部门</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ \App\Models\Asset::translateDept($asset->department) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">房间号</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->room ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">类别</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ \App\Models\Asset::translateCat($asset->category) }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">IP地址</dt>
                        <dd class="text-sm text-gray-900 mt-0.5 font-mono">{{ $asset->ip }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">MAC地址</dt>
                        <dd class="text-sm text-gray-900 mt-0.5 font-mono">{{ $asset->mac }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">品牌</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->brand ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">规格型号</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->model ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">SN序列号</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->sn ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">状态</dt>
                        <dd class="text-sm mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $asset->status === 'ZY' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $asset->status === 'XZ' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $asset->status === 'WX' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $asset->status === 'BF' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ \App\Models\Asset::translateStatus($asset->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">使用人</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->user ?: '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-500 uppercase">备注</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->remarks ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- 采购入库信息 -->
        @if($asset->intake_id || $asset->purchase_date || $asset->supplier)
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">采购入库信息</h3>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    @if($asset->intake_id && $asset->intake)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">入库单号</dt>
                        <dd class="text-sm mt-0.5">
                            <a href="{{ route('intakes.show', $asset->intake_id) }}" class="text-blue-600 hover:underline font-mono">{{ $asset->intake->order_no }}</a>
                        </dd>
                    </div>
                    @endif
                    @if($asset->purchase_date)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">采购日期</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->purchase_date->format('Y-m-d') }}</dd>
                    </div>
                    @endif
                    @if($asset->supplier)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">供应商</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->supplier }}</dd>
                    </div>
                    @endif
                    @if($asset->purchase_price)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">采购单价</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ number_format($asset->purchase_price, 2) }} 元</dd>
                    </div>
                    @endif
                    @if($asset->warranty_date)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">保修到期</dt>
                        <dd class="text-sm text-gray-900 mt-0.5">{{ $asset->warranty_date->format('Y-m-d') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        <!-- 操作按钮 -->
        @if(auth()->user()->isAdmin())
        <div class="bg-white rounded-xl shadow-sm p-4 flex space-x-3">
            <a href="{{ route('borrows.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                借用此设备
            </a>
            @if($asset->status !== 'BF')
            <a href="{{ route('disposals.create', ['assets' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                报废此设备
            </a>
            @endif
            @if($asset->status !== 'BF')
            <a href="{{ route('repairs.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                维修此设备
            </a>
            @endif
        </div>
        @endif

        <!-- 变更历史时间线 -->

        <!-- 维修历史 -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">维修历史</h3>
                    <p class="text-sm text-gray-500 mt-1">该资产的维修记录</p>
                </div>
                @if($asset->status !== 'BF' && auth()->user()->isAdmin())
                <a href="{{ route('repairs.create', ['asset_id' => $asset->id]) }}" class="text-sm text-blue-600 hover:underline">+ 新建维修单</a>
                @endif
            </div>
            <div class="p-6">
                @if($repairs->isEmpty())
                    <div class="text-center py-8 text-gray-400">
                        <svg class="h-10 w-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                        <p class="text-sm">暂无维修记录</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($repairs as $repair)
                        <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-4 min-w-0">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-600',
                                        'submitted' => 'bg-blue-100 text-blue-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-600',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$repair->status] ?? 'bg-gray-100 text-gray-600' }} shrink-0">
                                    {{ $repair->status_name }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        @if($repair->order_no)
                                            <a href="{{ route('repairs.show', $repair) }}" class="text-sm text-blue-600 hover:underline font-mono">{{ $repair->order_no }}</a>
                                        @else
                                            <span class="text-sm text-gray-400">草稿</span>
                                        @endif
                                        @if($repair->fault_category)
                                            <span class="text-xs text-gray-400">·</span>
                                            <span class="text-xs text-gray-500">{{ $repair->fault_category }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $repair->repair_date?->format('Y-m-d') ?? '-' }}
                                        @if($repair->vendor) · {{ $repair->vendor }} @endif
                                        @if($repair->cost) · 费用 {{ number_format($repair->cost, 2) }}元 @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('repairs.show', $repair) }}" class="text-xs text-gray-400 hover:text-blue-600 shrink-0 ml-4">详情 &rarr;</a>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">变更历史</h3>
                <p class="text-sm text-gray-500 mt-1">记录该资产的字段变更轨迹</p>
            </div>
            <div class="p-6">
                @if($logs->isEmpty())
                    <div class="text-center py-8 text-gray-400">
                        <svg class="h-10 w-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm">暂无变更记录</p>
                    </div>
                @else
                    <div class="relative">
                        <!-- 时间线竖线 -->
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="space-y-5">
                            @foreach($logs as $log)
                            <div class="relative flex items-start pl-10">
                                <!-- 时间线圆点 -->
                                <div class="absolute left-2.5 mt-1.5 w-3 h-3 rounded-full border-2 border-blue-400 bg-white"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 text-sm">
                                        <span class="font-medium text-gray-800">{{ $log->user_name }}</span>
                                        <span class="text-gray-400 text-xs">{{ $log->created_at->format('Y-m-d H:i') }}</span>
                                        @if(isset($logToOrder[$log->id]))
                                        <a href="{{ route('transfers.show', \App\Models\TransferOrder::where('asset_id', $asset->id)->whereJsonContains('log_ids', $log->id)->first()) }}"
                                           class="inline-flex items-center px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 no-underline">
                                            调拨单: {{ $logToOrder[$log->id] }}
                                        </a>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm">
                                        将
                                        <span class="font-medium text-gray-700">{{ $log->field_label }}</span>
                                        从
                                        <span class="px-1.5 py-0.5 bg-red-50 text-red-700 rounded text-xs line-through">{{ $log->old_value ?: '(空)' }}</span>
                                        <svg class="inline h-4 w-4 text-gray-400 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        <span class="px-1.5 py-0.5 bg-green-50 text-green-700 rounded text-xs font-medium">{{ $log->new_value ?: '(空)' }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
