<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">新建报废单</h2>
            <a href="{{ route('disposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('disposals.store') }}" id="disposalForm">
        @csrf
        <input type="hidden" name="_action" value="draft" id="formAction">

        <!-- 单据信息 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">报废信息</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">报废日期 <span class="text-red-500">*</span></label>
                    <input type="date" name="disposal_date" value="{{ old('disposal_date', date('Y-m-d')) }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('disposal_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">处置方式 <span class="text-red-500">*</span></label>
                    <select name="disposal_method" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="报废处置">报废处置</option>
                        <option value="捐赠">捐赠</option>
                        <option value="回收">回收</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">审批人</label>
                    <input type="text" name="approver" value="{{ old('approver') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">报废原因</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="请填写报废原因">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- 选择资产 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="disposalAssets()" x-init="init()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">选择待报废资产 <span class="text-sm text-gray-400 font-normal">（已选 <span x-text="selectedAssets.length" class="text-blue-600 font-medium"></span> 项）</span></h3>
                <button type="button" @click="showPicker = !showPicker" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 text-sm rounded-lg hover:bg-red-100">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span x-text="showPicker ? '收起选择器' : '选择资产'"></span>
                </button>
            </div>

            @error('asset_ids')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <!-- 已选资产列表 -->
            <div x-show="selectedAssets.length > 0" class="mb-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">资产编号</th>
                                <th class="px-3 py-2 text-left font-medium">名称</th>
                                <th class="px-3 py-2 text-left font-medium">类别</th>
                                <th class="px-3 py-2 text-left font-medium">部门</th>
                                <th class="px-3 py-2 text-left font-medium">使用人</th>
                                <th class="px-3 py-2 text-left font-medium">状态</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(asset, idx) in selectedAssets" :key="asset.id">
                                <tr class="border-t border-gray-100">
                                    <td class="px-3 py-2 font-mono text-xs text-blue-600" x-text="asset.asset_code"></td>
                                    <td class="px-3 py-2" x-text="asset.name"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.category_name || asset.category"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.department_name || asset.department"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.user || '-'"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.status_name || asset.status"></td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'asset_ids[]'" :value="asset.id">
                                        <button type="button" @click="removeAsset(idx)" class="text-red-400 hover:text-red-600 p-1">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 资产选择器 -->
            <div x-show="showPicker" x-transition class="border border-gray-200 rounded-lg p-4">
                <div class="flex gap-3 mb-3">
                    <input type="text" x-model="searchQ" @input.debounce.300ms="searchAssets()" placeholder="搜索资产编号/名称/IP" class="flex-1 border border-gray-300 rounded-lg py-2 px-3 text-sm">
                    <button type="button" @click="searchAssets()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">搜索</button>
                </div>
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium w-10"></th>
                                <th class="px-3 py-2 text-left font-medium">编号</th>
                                <th class="px-3 py-2 text-left font-medium">名称</th>
                                <th class="px-3 py-2 text-left font-medium">类别</th>
                                <th class="px-3 py-2 text-left font-medium">部门</th>
                                <th class="px-3 py-2 text-left font-medium">使用人</th>
                                <th class="px-3 py-2 text-left font-medium">状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="a in searchResults" :key="a.id">
                                <tr class="border-t border-gray-100 hover:bg-blue-50 cursor-pointer" @click="toggleAsset(a)">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" :checked="isSelected(a.id)" class="rounded border-gray-300" @click.stop="toggleAsset(a)">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="a.asset_code"></td>
                                    <td class="px-3 py-2" x-text="a.name"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="a.category_name || a.category"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="a.department_name || a.department"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="a.user || '-'"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="a.status_name || a.status"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p x-show="searchResults.length === 0 && searchQ.length > 0" class="text-center text-gray-400 py-4">未找到匹配资产</p>
                </div>
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('disposals.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
            <button type="button" @click="submitDraft()" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
            <button type="button" @click="submitFinal()" class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">提交报废</button>
        </div>
    </form>

    <script>
    function disposalAssets() {
        return {
            showPicker: true,
            searchQ: '',
            searchResults: [],
            selectedAssets: [],
            init() {
                this.searchAssets();
                @if($preselectIds)
                const ids = '{{ $preselectIds }}'.split(',').filter(Boolean);
                if (ids.length) {
                    fetch('/assets/json?ids=' + ids.join(','))
                        .then(r => r.json())
                        .then(d => { this.selectedAssets = d.data || []; });
                }
                @endif
            },
            async searchAssets() {
                const q = this.searchQ.trim();
                const url = q ? '/assets/search?q=' + encodeURIComponent(q) : '/assets/json?per_page=50';
                const res = await fetch(url);
                const data = await res.json();
                this.searchResults = Array.isArray(data) ? data : (data.data || []);
            },
            isSelected(id) {
                return this.selectedAssets.some(a => a.id === id);
            },
            toggleAsset(asset) {
                const idx = this.selectedAssets.findIndex(a => a.id === asset.id);
                if (idx >= 0) {
                    this.selectedAssets.splice(idx, 1);
                } else {
                    this.selectedAssets.push(asset);
                }
            },
            removeAsset(idx) {
                this.selectedAssets.splice(idx, 1);
            }
        };
    }
    function submitDraft() {
        document.getElementById('formAction').value = 'draft';
        document.getElementById('disposalForm').submit();
    }
    function submitFinal() {
        document.getElementById('formAction').value = 'submit';
        document.getElementById('disposalForm').submit();
    }
    </script>
</x-app-layout>
