<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">用户管理</h2>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                添加用户
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm">
        <!-- 搜索和筛选 -->
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索用户名、姓名、部门..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select name="role" class="border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">全部角色</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>管理员</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>普通用户</option>
                </select>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">搜索</button>
            </form>
        </div>

        <!-- 表格 -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">用户名</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">姓名</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">角色</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">部门</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">创建时间</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $user->id }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $user->username }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $user->isAdmin() ? '管理员' : '普通用户' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $user->department ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? '启用' : '禁用' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $user->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800">编辑</a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('确定删除此用户？')" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800">删除</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
