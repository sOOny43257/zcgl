<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">添加资产</h2>
            <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('assets.store') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">自有编号 <span class="text-gray-400 text-xs">（留空自动生成）</span></label>
                        <input type="text" name="asset_code" value="{{ old('asset_code') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('asset_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">财务编码</label>
                        <input type="text" name="financial_code" value="{{ old('financial_code') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">资产名称</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">部门</label>
                        <x-dept-autocomplete name="department" value="{{ old('department') }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">房间号</label>
                        <input type="text" name="room" value="{{ old('room') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IP地址 <span class="text-red-500">*</span></label>
                        <input type="text" name="ip" value="{{ old('ip') }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('ip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MAC地址 <span class="text-red-500">*</span></label>
                        <input type="text" name="mac" value="{{ old('mac') }}" required placeholder="00:1A:2B:3C:4D:5E" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('mac')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SN序列号</label>
                        <input type="text" name="sn" value="{{ old('sn') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">品牌</label>
                        <input type="text" name="brand" value="{{ old('brand') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">规格型号</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">类别</label>
                        <x-code-autocomplete name="category" type="category" value="{{ old('category', 'DTFN') }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
                        <x-code-autocomplete name="status" type="status" value="{{ old('status', 'ZY') }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">使用人</label>
                        <input type="text" name="user" value="{{ old('user') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea name="remarks" rows="3" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('assets.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">保存</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
