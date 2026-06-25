@php $isAdmin = auth()->user()->isAdmin(); @endphp

<aside x-data="{ collapsed: false }" class="fixed inset-y-0 left-0 z-50 flex flex-col sidebar-glass text-white transition-all duration-300"
       :class="collapsed ? 'w-16' : 'w-64'">
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-white/10">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 overflow-hidden">
            <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <span class="text-lg font-semibold whitespace-nowrap tracking-tight" x-show="!collapsed">资产管家</span>
        </a>
        <button @click="collapsed = !collapsed" class="p-1.5 rounded-xl hover:bg-white/10 hidden sm:block transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

    <!-- Nav Links -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        @php
        $links = [
            ['route' => 'dashboard', 'label' => '仪表盘', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['route' => 'assets.index', 'label' => '资产管理', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
            ['route' => 'assets.check', 'label' => '资产盘点表', 'icon' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z'],
            ['route' => 'transfers.index', 'label' => '资产调拨单', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
            ['route' => 'api-docs', 'label' => 'API 文档', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        ];

        $adminLinks = [
            ['route' => 'codes.index', 'label' => '数据字典', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
            ['route' => 'data-updates.index', 'label' => '数据更新', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
            ['route' => 'users.index', 'label' => '用户管理', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['route' => 'system.index', 'label' => '系统管理', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
        ];
        @endphp

        @foreach($links as $link)
        @php $active = request()->routeIs($link['route']) || request()->routeIs($link['route'] . '.*'); @endphp
        <a href="{{ route($link['route'], $link['route'] === 'assets.check' ? ['department' => $isAdmin ? '' : auth()->user()->department] : []) }}"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl transition-all duration-200 {{ $active ? 'bg-white/15 text-white shadow-lg shadow-black/10' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $link['icon'] }}"/></svg>
            <span x-show="!collapsed" class="text-sm font-medium whitespace-nowrap">{{ $link['label'] }}</span>
        </a>
        @endforeach

        <!-- 导入导出（二级菜单） -->
        @php $ioActive = request()->routeIs('assets.exportCsv') || request()->routeIs('assets.importForm'); @endphp
        <div x-data="{ ioOpen: {{ $ioActive ? 'true' : 'false' }} }">
            <a href="#" @click.prevent="ioOpen = !ioOpen"
               class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl transition-all duration-200 cursor-pointer {{ $ioActive ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-show="!collapsed" class="text-sm font-medium whitespace-nowrap flex-1">导入导出</span>
                <svg x-show="!collapsed" class="h-3 w-3 shrink-0 transition-transform opacity-50" :class="ioOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <div x-show="!collapsed && ioOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 border-l border-white/10 pl-3 space-y-0.5 mt-0.5">
                <a href="{{ route('assets.exportPreview') }}"
                   class="flex items-center space-x-2 px-3 py-2 rounded-2xl transition-all duration-200 text-sm {{ request()->routeIs('assets.exportPreview') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                    <span class="whitespace-nowrap text-xs">导出资产 CSV</span>
                </a>
                @if($isAdmin)
                <a href="{{ route('assets.importForm') }}"
                   class="flex items-center space-x-2 px-3 py-2 rounded-2xl transition-all duration-200 text-sm {{ request()->routeIs('assets.importForm') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                    <span class="whitespace-nowrap text-xs">导入资产 CSV</span>
                </a>
                @endif
            </div>
        </div>

        <!-- 借用管理（二级菜单） -->
        @php $borrowActive = request()->routeIs('borrows.*'); @endphp
        <div x-data="{ borrowOpen: {{ $borrowActive ? 'true' : 'false' }} }">
            <a href="{{ route('borrows.manage') }}"
               @click.prevent="borrowOpen = !borrowOpen"
               class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl transition-all duration-200 cursor-pointer {{ $borrowActive ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="!collapsed" class="text-sm font-medium whitespace-nowrap flex-1">借用管理</span>
                <svg x-show="!collapsed" class="h-3 w-3 shrink-0 transition-transform opacity-50" :class="borrowOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <div x-show="!collapsed && borrowOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 border-l border-white/10 pl-3 space-y-0.5 mt-0.5">
                <a href="{{ route('borrows.manage') }}"
                   class="flex items-center space-x-2 px-3 py-2 rounded-2xl transition-all duration-200 text-sm {{ request()->routeIs('borrows.manage') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                    <span class="whitespace-nowrap text-xs">借用中管理</span>
                </a>
                <a href="{{ route('borrows.index') }}"
                   class="flex items-center space-x-2 px-3 py-2 rounded-2xl transition-all duration-200 text-sm {{ request()->routeIs('borrows.index') || request()->routeIs('borrows.show') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
                    <span class="whitespace-nowrap text-xs">借用记录</span>
                </a>
            </div>
        </div>

        @if($isAdmin)
        <div class="pt-3 mt-3 border-t border-white/10">
            <div x-show="!collapsed" class="px-3 pb-1">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">管理功能</span>
            </div>
        </div>
        @foreach($adminLinks as $link)
        @php $active = request()->routeIs($link['route']) || request()->routeIs($link['route'] . '.*'); @endphp
        <a href="{{ route($link['route']) }}"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl transition-all duration-200 {{ $active ? 'bg-white/15 text-white shadow-lg shadow-black/10' : 'text-gray-400 hover:text-white hover:bg-white/8' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $link['icon'] }}"/></svg>
            <span x-show="!collapsed" class="text-sm font-medium whitespace-nowrap">{{ $link['label'] }}</span>
        </a>
        @endforeach
        @endif
    </nav>

    <!-- User Footer -->
    <div class="border-t border-white/10 p-3">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                <span class="text-xs font-semibold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div x-show="!collapsed" class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-gray-400">
                    {{ $isAdmin ? '管理员' : '普通用户' }}
                </p>
            </div>
        </div>
        <div x-show="!collapsed" class="mt-2 flex space-x-1.5">
            <a href="{{ route('profile.edit') }}" class="flex-1 text-center text-[11px] text-gray-400 hover:text-white py-1.5 rounded-xl hover:bg-white/10 transition-colors">资料</a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button class="w-full text-center text-[11px] text-gray-400 hover:text-white py-1.5 rounded-xl hover:bg-white/10 transition-colors">退出</button>
            </form>
        </div>
    </div>
</aside>

<!-- Mobile Bottom Nav -->
<nav class="fixed bottom-0 inset-x-0 glass z-50 sm:hidden border-t border-gray-200/50">
    <div class="grid grid-cols-4 h-14">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center text-[11px] {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            仪表盘
        </a>
        <a href="{{ route('assets.index') }}" class="flex flex-col items-center justify-center text-[11px] {{ request()->routeIs('assets.*') && !request()->routeIs('assets.export') ? 'text-blue-600' : 'text-gray-400' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            资产
        </a>
        <a href="{{ route('transfers.index') }}" class="flex flex-col items-center justify-center text-[11px] {{ request()->routeIs('transfers.*') ? 'text-blue-600' : 'text-gray-400' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            调拨
        </a>
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center text-[11px] {{ request()->routeIs('profile.*') ? 'text-blue-600' : 'text-gray-400' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            我的
        </a>
    </div>
</nav>
