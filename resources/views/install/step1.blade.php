<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>系统安装 - 数据库配置</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg bg-white rounded-3xl shadow-xl p-8">
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">和平区税务局资产管理系统</h1>
        <p class="text-gray-500 mt-1">第一步：配置数据库连接</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-4 text-sm">{{ $errors->first() }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ url('/install/setup') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">数据库主机</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">端口</label>
            <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">数据库名</label>
            <input type="text" name="db_name" value="{{ old('db_name', 'zcgl') }}" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-1">请先在 MySQL 中创建好该数据库</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
            <input type="text" name="db_user" value="{{ old('db_user', 'root') }}" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
            <input type="password" name="db_pass" value="{{ old('db_pass') }}"
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="w-full py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
            测试连接并初始化数据库
        </button>
    </form>
</div>
</body>
</html>
