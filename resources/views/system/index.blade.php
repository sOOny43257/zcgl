<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">系统管理</h2>
    </x-slot>

    <div class="space-y-6 max-w-3xl mx-auto">
        <!-- 数据库信息 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">数据库状态</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 rounded-2xl p-4">
                    <p class="text-gray-500 text-xs">数据库名</p>
                    <p class="font-mono font-medium">{{ config('database.connections.mysql.database') }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4">
                    <p class="text-gray-500 text-xs">数据大小</p>
                    <p class="font-mono font-medium">{{ $dbSize }}</p>
                </div>
            </div>

            <!-- mysqldump 路径检测 -->
            @php
                $mysqldumpOk = $mysqldumpPath !== 'mysqldump' && is_executable($mysqldumpPath);
            @endphp
            <div class="mt-4 bg-gray-50 rounded-2xl p-4 text-sm">
                <p class="text-gray-500 text-xs mb-1">备份工具路径</p>
                @if($mysqldumpOk)
                    <p class="font-mono text-green-700">{{ $mysqldumpPath }}</p>
                @else
                    <p class="font-mono text-red-600">未找到 mysqldump</p>
                    <p class="text-xs text-gray-500 mt-1">请在 .env 中配置 <code class="bg-gray-200 px-1 rounded">MYSQL_BIN_DIR</code>，例如：<code class="bg-gray-200 px-1 rounded">MYSQL_BIN_DIR=/Applications/XAMPP/xamppfiles/bin</code></p>
                @endif
            </div>

            <form method="POST" action="{{ route('system.init') }}" class="mt-4" onsubmit="return confirm('⚠️ 初始化将清空所有数据并重建数据库！\n\n确定要继续吗？此操作不可恢复。')">
                @csrf
                <button class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700">初始化数据库</button>
                <p class="text-xs text-red-500 mt-2">将执行 migrate:fresh --seed，清空所有数据后重建表结构和默认数据</p>
            </form>
        </div>

        <!-- 备份 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">数据备份</h3>
            <div class="flex gap-3 mb-4">
                <form method="POST" action="{{ route('system.backup') }}">
                    @csrf
                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">立即备份</button>
                </form>
            </div>

            @if(!empty($backups))
            <div class="space-y-2">
                <p class="text-xs text-gray-500">备份历史：</p>
                @foreach($backups as $b)
                <div class="flex items-center justify-between bg-gray-50 rounded-2xl px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $b['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $b['date'] }} · {{ $b['size'] }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('system.backup.download', $b['name']) }}" class="text-xs text-blue-600 hover:text-blue-800">下载</a>
                        <form method="POST" action="{{ route('system.restore') }}">
                            @csrf
                            <input type="hidden" name="backup_file" value="{{ $b['name'] }}">
                            <button class="text-xs text-amber-600 hover:text-amber-800" onclick="return confirm('确定还原此备份？当前数据将被覆盖。')">还原</button>
                        </form>
                        <form method="POST" action="{{ route('system.backup.delete') }}">
                            @csrf
                            <input type="hidden" name="file" value="{{ $b['name'] }}">
                            <button class="text-xs text-red-500 hover:text-red-700" onclick="return confirm('确定删除此备份？')">删除</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">暂无备份文件</p>
            @endif
        </div>
    </div>
</x-app-layout>
