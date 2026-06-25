<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">访问日志</h2>
                <p class="text-sm text-gray-500 mt-0.5">记录用户访问行为（IP、浏览器、平台）</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('system.logs.clear') }}" onsubmit="return confirm('确定清空所有访问日志？')">
                    @csrf
                    <button class="px-3 py-2 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50">清空日志</button>
                </form>
                <a href="{{ route('system.index') }}" class="px-3 py-2 border border-gray-200 text-gray-600 text-sm rounded-xl hover:bg-gray-50">返回系统管理</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
        <!-- 筛选 -->
        <div class="p-4 border-b">
            <form method="GET" class="flex gap-3">
                <input type="text" name="user" value="{{ request('user') }}" placeholder="用户名筛选..." class="border border-gray-200 rounded-xl px-3 py-2 text-sm flex-1">
                <input type="text" name="ip" value="{{ request('ip') }}" placeholder="IP筛选..." class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-40">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700">筛选</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">时间</th>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">用户</th>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">IP</th>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">浏览器</th>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">平台</th>
                        <th class="px-3 py-3 text-left text-xs text-gray-500">URL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($logs as $log)
                    <tr class="hover:bg-gray-50/50 text-sm">
                        <td class="px-3 py-2.5 text-gray-500 whitespace-nowrap text-xs">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-3 py-2.5 text-gray-700">{{ $log->user_name ?: '访客' }}</td>
                        <td class="px-3 py-2.5 font-mono text-xs text-gray-600">{{ $log->ip }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $log->browser }}</td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $log->platform }}</td>
                        <td class="px-3 py-2.5 text-gray-500 text-xs max-w-xs truncate">{{ $log->url }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
