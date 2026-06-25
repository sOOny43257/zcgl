<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">CSV导入{{ $typeLabel }}</h2>
            <a href="{{ route('codes.index', ['type' => $type]) }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('codes.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">选择CSV文件</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                        @error('csv_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">CSV文件格式要求</h4>
                        <ul class="text-xs text-blue-700 space-y-1">
                            <li>第一行为表头，必须包含 <code class="bg-blue-100 px-1 rounded">code</code> 和 <code class="bg-blue-100 px-1 rounded">name</code> 列</li>
                            <li>编码已存在时自动更新名称，不存在则新增</li>
                            <li>文件编码建议使用 UTF-8</li>
                        </ul>
                        <div class="mt-3 text-xs text-blue-600 font-mono bg-white rounded p-2">
                            code,name<br>
                            CW,财务部<br>
                            RS,人事部
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('codes.index', ['type' => $type]) }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">开始导入</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
