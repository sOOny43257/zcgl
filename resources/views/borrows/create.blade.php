<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">设备借用登记</h2>
            <a href="{{ route('borrows.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto" x-data="{ selectedIds: [], selectedAssets: [] }"
         @asset-selected="selectedAssets = $event.detail.assets; selectedIds = $event.detail.ids">
        <form method="POST" action="{{ route('borrows.store') }}" id="borrowForm">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="asset_ids[]" :value="id">
            </template>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800">选择设备</h3>
                    <x-asset-selector trigger-label="搜索并选择设备" :multiple="true" context="borrow" />
                </div>

                <!-- 已选资产预览 -->
                <div x-show="selectedAssets.length > 0" x-cloak>
                    <p class="text-xs text-gray-500 mb-2">已选 <span class="font-bold text-blue-600" x-text="selectedAssets.length"></span> 台：</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(a, idx) in selectedAssets" :key="a.id">
                            <span class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 rounded-xl text-sm">
                                <span class="font-mono font-bold" x-text="a.asset_code"></span>
                                <span class="mx-1.5 text-blue-400">|</span>
                                <span x-text="a.name || '(未命名)'"></span>
                                <span class="ml-2 text-xs" :class="a.status === '在用' ? 'text-green-600' : 'text-yellow-600'" x-text="a.status"></span>
                                <button type="button" @click="selectedAssets.splice(idx,1); selectedIds.splice(idx,1)" class="ml-2 text-blue-500 hover:text-red-500 font-bold">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>
                <div x-show="selectedAssets.length === 0" class="text-sm text-gray-400 text-center py-8">
                    请点击上方按钮选择要借用的设备
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">借用信息</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">借用人 <span class="text-red-500">*</span></label>
                        <input type="text" name="borrower" required class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">借用部门</label>
                        <select name="department" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">-- 请选择 --</option>
                            @foreach(\App\Models\DepartmentCode::type('department')->orderBy('code')->get() as $dept)
                                <option value="{{ $dept->code }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">借用日期 <span class="text-red-500">*</span></label>
                        <input type="date" name="borrow_date" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">预计归还日期</label>
                        <input type="date" name="expected_return_date" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <textarea name="remarks" rows="2" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('borrows.index') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">取消</a>
                    <button type="submit" :disabled="selectedIds.length === 0"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        确认借用（<span x-text="selectedIds.length"></span> 台）
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
