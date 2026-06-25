<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>系统安装 - 创建管理员</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-emerald-50 to-teal-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg bg-white rounded-3xl shadow-xl p-8">
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/30">
            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">数据库初始化完成</h1>
        <p class="text-gray-500 mt-1">第二步：创建管理员账号</p>
    </div>

    @if(isset($migrateOutput))
    <div class="bg-gray-50 rounded-xl p-3 mb-4 max-h-32 overflow-y-auto">
        <pre class="text-xs text-gray-600 whitespace-pre-wrap">{{ $migrateOutput }}</pre>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-4 text-sm">{{ $errors->first() }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ url('/install/complete') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
            <input type="text" name="username" value="{{ old('username') }}" required minlength="3"
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
            <input type="password" name="password" required minlength="6"
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">确认密码</label>
            <input type="password" name="password_confirmation" required minlength="6"
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="w-full py-3 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors">
            创建管理员并完成安装
        </button>
    </form>
</div>
</body>
</html>
