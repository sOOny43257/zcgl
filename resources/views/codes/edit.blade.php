<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">编辑{{ $typeLabel }}</h2>
            <a href="{{ route('codes.index', ['type' => $code->type]) }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('codes.update', $code) }}">
                @csrf @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">编号</label>
                        <input type="text" name="code" value="{{ old('code', $code->code) }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm font-mono focus:ring-2 focus:ring-blue-500">
                        @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">名称</label>
                        <input type="text" name="name" value="{{ old('name', $code->name) }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('codes.index', ['type' => $code->type]) }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">保存</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
