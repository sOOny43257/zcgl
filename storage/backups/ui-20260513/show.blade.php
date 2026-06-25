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

        <!-- 操作按钮 -->
        @if(auth()->user()->isAdmin())
        <div class="bg-white rounded-xl shadow-sm p-4 flex space-x-3">
            <a href="{{ route('borrows.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                借用此设备
            </a>
        </div>
        @endif

        <!-- 变更历史时间线 -->
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
