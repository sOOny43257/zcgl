<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">仪表盘</h2>
                <p class="text-sm text-gray-500 mt-0.5">资产概览与快捷操作</p>
            </div>
            <span class="text-xs text-gray-400">{{ date('Y年m月d日') }}</span>
        </div>
    </x-slot>

    <!-- 统计卡片行 -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @php
        $cards = [
            ['label' => '总资产', 'value' => $totalAssets, 'color' => 'blue', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4', 'route' => route('assets.index')],
            ['label' => '在用', 'value' => $statusCounts['ZY'] ?? 0, 'color' => 'emerald', 'icon' => 'M5 13l4 4L19 7', 'route' => route('assets.index', ['statuses[]' => 'ZY'])],
            ['label' => '借用中', 'value' => $statusCounts['JIE'] ?? 0, 'color' => 'purple', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', 'route' => route('borrows.manage')],
            ['label' => '闲置', 'value' => $statusCounts['XZ'] ?? 0, 'color' => 'amber', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'route' => route('assets.index', ['statuses[]' => 'XZ'])],
            ['label' => '维修/待报废', 'value' => ($statusCounts['WX'] ?? 0) + ($statusCounts['DBF'] ?? 0), 'color' => 'orange', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'route' => route('assets.index', ['statuses[]' => 'WX', 'statuses[]' => 'DBF'])],
        ];
        @endphp

        @foreach($cards as $card)
        <a href="{{ $card['route'] }}" class="card-hover bg-white rounded-3xl p-5 shadow-sm border border-gray-100/50 group">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-{{ $card['color'] }}-50 group-hover:bg-{{ $card['color'] }}-100 transition-colors">
                    <svg class="h-5 w-5 text-{{ $card['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
                </div>
                <svg class="h-4 w-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $card['value'] }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $card['label'] }}</p>
        </a>
        @endforeach
    </div>

    <!-- 本月入库/报废 -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <a href="{{ route('intakes.index') }}" class="card-hover bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-3xl p-5 shadow-sm border border-blue-200/50 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">本月入库</p>
                    <p class="text-2xl font-bold text-blue-800 mt-1">{{ $intakeThisMonth ?? 0 }} <span class="text-sm font-normal text-blue-500">批次</span></p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-2xl flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
            </div>
        </a>
        <a href="{{ route('disposals.index') }}" class="card-hover bg-gradient-to-br from-red-50 to-red-100/50 rounded-3xl p-5 shadow-sm border border-red-200/50 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 font-medium">本月报废</p>
                    <p class="text-2xl font-bold text-red-800 mt-1">{{ $disposalThisMonth ?? 0 }} <span class="text-sm font-normal text-red-500">批次</span></p>
                </div>
                <div class="w-12 h-12 bg-red-500/20 rounded-2xl flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
            </div>
        </a>
    </div>

    <!-- 图表 + 部门 + 动态 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 资产分类环图 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 card-hover">
            <h3 class="text-base font-semibold text-gray-800 mb-4">资产分类分布</h3>
            <div class="relative" style="height:220px;">
                <canvas id="categoryChart"></canvas>
            </div>
            <script src="{{ asset('vendor/chart.min.js') }}"></script>
            <script>
            @php $chartData = $catCounts->map(fn($c) => ['category' => \App\Models\Asset::translateCat($c->category), 'cnt' => $c->cnt]); @endphp
            (function(){var d=@json($chartData);var c=['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#6366f1','#84cc16'];function r(){var x=document.getElementById('categoryChart');if(!x)return;if(typeof Chart==='undefined'){setTimeout(r,200);return}new Chart(x,{type:'doughnut',data:{labels:d.map(function(i){return i.category}),datasets:[{data:d.map(function(i){return i.cnt}),backgroundColor:c.slice(0,d.length),borderColor:'#fff',borderWidth:3,hoverBorderWidth:4,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false}}}})}r()})();
            </script>
            <div class="mt-4 space-y-2">
                @php $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#6366f1','#84cc16']; @endphp
                @foreach($catCounts->take(5) as $i => $c)
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full mr-2" style="background:{{ $colors[$i%8] }}"></span>
                        <span class="text-gray-600">{{ \App\Models\Asset::translateCat($c->category) }}</span>
                    </div>
                    <span class="font-medium text-gray-800">{{ $c->cnt }} 台</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- 部门分布 + 进度条 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 card-hover">
            <h3 class="text-base font-semibold text-gray-800 mb-4">部门资产分布</h3>
            <div class="space-y-4">
                @foreach($depts as $d)
                <a href="{{ route('assets.index', ['departments[]' => $d->department]) }}" class="block group">
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-gray-600 group-hover:text-gray-900 transition-colors">{{ \App\Models\Asset::translateDept($d->department) }}</span>
                        <span class="font-medium text-gray-800">{{ $d->cnt }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full transition-all duration-700 group-hover:from-blue-500 group-hover:to-blue-700"
                             style="width:{{ $totalAssets > 0 ? $d->cnt/$totalAssets*100 : 0 }}%"></div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <!-- 快捷操作 + 最近动态 -->
        <div class="space-y-4">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 card-hover">
                <h3 class="text-base font-semibold text-gray-800 mb-4">快捷操作</h3>
                <div class="space-y-2">
                    <a href="{{ route('assets.create') }}" class="flex items-center p-3 rounded-2xl bg-blue-50 hover:bg-blue-100 transition-colors group">
                        <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-sm font-medium text-blue-700">添加新资产</span>
                    </a>
                    <a href="{{ route('intakes.create') }}" class="flex items-center p-3 rounded-2xl bg-cyan-50 hover:bg-cyan-100 transition-colors group">
                        <div class="w-8 h-8 bg-cyan-500 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                        <span class="text-sm font-medium text-cyan-700">资产入库登记</span>
                    </a>
                    <a href="{{ route('disposals.create') }}" class="flex items-center p-3 rounded-2xl bg-red-50 hover:bg-red-100 transition-colors group">
                        <div class="w-8 h-8 bg-red-500 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <span class="text-sm font-medium text-red-700">资产报废申请</span>
                    </a>
                    <a href="{{ route('borrows.create') }}" class="flex items-center p-3 rounded-2xl bg-purple-50 hover:bg-purple-100 transition-colors group">
                        <div class="w-8 h-8 bg-purple-500 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <span class="text-sm font-medium text-purple-700">设备借用登记</span>
                    </a>
                    <a href="{{ route('assets.exportCsv') }}" class="flex items-center p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 transition-colors group">
                        <div class="w-8 h-8 bg-emerald-500 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-sm font-medium text-emerald-700">导出资产 CSV</span>
                    </a>
                </div>
            </div>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 card-hover">
                <h3 class="text-base font-semibold text-gray-800 mb-3">状态概览</h3>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['ZY' => ['emerald','✅','在用'], 'XZ' => ['amber','⏳','闲置'], 'JIE' => ['purple','📤','借用'], 'WX' => ['orange','🔧','维修'], 'DBF' => ['red','⚠️','待报废'], 'BF' => ['gray','🗑️','报废']] as $sn => $cfg)
                    <a href="{{ route('assets.index', ['statuses[]' => $sn]) }}" class="block p-3 rounded-2xl border border-gray-100 hover:border-{{ $cfg[0] }}-200 hover:shadow-sm transition-all text-center">
                        <div class="text-xl mb-1">{{ $cfg[1] }}</div>
                        <p class="text-lg font-bold text-gray-800">{{ $statusCounts[$sn] ?? 0 }}</p>
                        <p class="text-[11px] text-gray-500">{{ $cfg[2] }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
