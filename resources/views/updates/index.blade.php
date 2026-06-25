<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">系统更新</h2>
    </x-slot>

    <div class="space-y-4">
        {{-- 上传更新包 --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-1">上传更新包</h3>
            <p class="text-sm text-gray-500 mb-4">当前版本：<strong class="text-blue-600">{{ $version }}</strong></p>

            <form method="POST" action="{{ route('updates.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="file" name="update_file" accept=".tar.gz,.tgz"
                           class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm flex-1 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">
                        上传并更新
                    </button>
                </div>
                <p class="text-xs text-gray-400">支持 *.tar.gz 格式的更新包</p>
            </form>

            @if(session('error'))
            <div class="mt-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 text-sm">{{ session('error') }}</div>
            @endif
        </div>

        {{-- 更新历史 --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50/50">
                <h3 class="text-sm font-semibold text-gray-700">更新历史</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">版本</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">日期</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">更新说明</th>
                            <th class="px-4 py-3 text-center text-xs text-gray-500 w-20">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($history as $h)
                        <tr class="{{ $h['version'] === $version ? 'bg-blue-50/50' : '' }}">
                            <td class="px-4 py-3 font-mono font-medium">
                                {{ $h['version'] }}
                                @if($h['version'] === $version)
                                <span class="text-[10px] text-blue-500 ml-1">● 当前</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $h['date'] }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $h['desc'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($h['version'] !== $version)
                                <form method="POST" action="{{ route('updates.rollback') }}" onsubmit="return confirm('确定回滚至 {{ $h['version'] }} 吗？\n\n这将撤销该版本之后的所有更新。')">
                                    @csrf
                                    <input type="hidden" name="version" value="{{ $h['version'] }}">
                                    <button class="text-orange-500 hover:text-orange-700 text-xs font-medium">回滚至此</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
