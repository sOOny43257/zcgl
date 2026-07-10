<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">编辑维修单 <span class="text-sm text-gray-400 font-normal">草稿 #{{ $repair->id }}</span></h2>
            <a href="{{ route('repairs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('repairs.update', $repair) }}" id="repairForm" enctype="multipart/form-data"
          x-data="repairForm()" x-init="init()">
        @csrf
        @method('PUT')
        <input type="hidden" name="_action" value="save" id="formAction">

        <!-- 维修信息 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">维修信息</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">送修日期 <span class="text-red-500">*</span></label>
                    <input type="date" name="repair_date" value="{{ old('repair_date', $repair->repair_date?->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">故障类别</label>
                    <select name="fault_category" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">请选择</option>
                        @foreach(\App\Models\Repair::FAULT_CATEGORIES as $cat)
                            <option value="{{ $cat }}" {{ old('fault_category', $repair->fault_category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">维修方式</label>
                    <select name="repair_method" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">请选择</option>
                        @foreach(\App\Models\Repair::REPAIR_METHODS as $method)
                            <option value="{{ $method }}" {{ old('repair_method', $repair->repair_method) === $method ? 'selected' : '' }}>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">维修单位/人员</label>
                    <input type="text" name="vendor" value="{{ old('vendor', $repair->vendor) }}"
                           class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">维修费用（元）</label>
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $repair->cost) }}"
                           class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">预计完成日期</label>
                    <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date', $repair->expected_completion_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">故障描述 <span class="text-red-400 text-xs">（提交时必填）</span></label>
                    <textarea name="fault_description" rows="3"
                              class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('fault_description', $repair->fault_description) }}</textarea>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks', $repair->remarks) }}"
                           class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- 选择资产 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">
                    选择维修资产
                    <span class="text-sm text-gray-400 font-normal ml-2">（已选 <span x-text="selectedAssets.length" class="text-blue-600 font-medium"></span> 项）</span>
                </h3>
                <button type="button" @click="showPicker = !showPicker" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 text-sm rounded-lg hover:bg-blue-100">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span x-text="showPicker ? '收起选择器' : '选择资产'"></span>
                </button>
            </div>

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
                                    <td class="px-3 py-2" x-text="asset.name || '-'"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.category_name || asset.category"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.department_name || asset.department"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.user || '-'"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="asset.status_name || asset.status"></td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="asset_ids[]" :value="asset.id">
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

            <p x-show="selectedAssets.length === 0" class="text-sm text-gray-400 mb-4">请在下方搜索并选择设备</p>

            <!-- 资产选择器 -->
            <div x-show="showPicker" x-transition class="border border-gray-200 rounded-lg p-4">
                <div class="flex gap-3 mb-3">
                    <input type="text" x-model="searchQ" @input.debounce.300ms="searchAssets()" placeholder="搜索资产编号/名称/IP/SN..."
                           class="flex-1 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500">
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
                                <tr class="border-t border-gray-100 hover:bg-blue-50 cursor-pointer"
                                    :class="isSelected(a.id) ? 'bg-blue-50' : ''"
                                    @click="toggleAsset(a)">
                                    <td class="px-3 py-2" @click.stop>
                                        <input type="checkbox" :checked="isSelected(a.id)"
                                               @change="toggleAsset(a)"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="a.asset_code"></td>
                                    <td class="px-3 py-2" x-text="a.name || '-'"></td>
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

        <!-- 已有附件 -->
        @if($repair->attachments && count($repair->attachments) > 0)
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">已有附件</h3>
            <div class="space-y-2">
                @foreach($repair->attachments as $path)
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <a href="{{ Storage::url($path) }}" target="_blank" class="text-sm text-blue-600 hover:underline">{{ basename($path) }}</a>
                    <label class="flex items-center text-xs text-red-500">
                        <input type="checkbox" name="remove_attachments[]" value="{{ $path }}" class="mr-1 rounded border-gray-300"> 删除
                    </label>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- 新附件 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">添加附件</h3>
            <input type="file" name="attachments[]" multiple
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </div>

        <!-- 操作按钮 -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('repairs.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
            <button type="button" @click="submitDelete()" class="px-4 py-2.5 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50">删除草稿</button>
            <button type="button" @click="submitDraft()" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
            <button type="button" @click="submitFinal()" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">提交维修</button>
        </div>
    </form>

    <script>
    function repairForm() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        return {
            showPicker: false,
            searchQ: '',
            searchResults: [],
            selectedAssets: [],

            init() {
                @if($repair->asset)
                this.selectedAssets = [@json($repair->asset->only('id', 'asset_code', 'name', 'category', 'department', 'user', 'status'))];
                @endif
                this.searchAssets();
            },

            async searchAssets() {
                const q = this.searchQ.trim();
                const url = q ? baseUrl + '/assets/search?q=' + encodeURIComponent(q) : baseUrl + '/assets/json?per_page=50';
                try {
                    const res = await fetch(url);
                    const data = await res.json();
                    this.searchResults = (Array.isArray(data) ? data : (data.data || []))
                        .filter(a => !this.isSelected(a.id));
                } catch(e) { this.searchResults = []; }
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
            },

            submitDraft() {
                document.getElementById('formAction').value = 'save';
                document.getElementById('repairForm').submit();
            },

            submitFinal() {
                document.getElementById('formAction').value = 'submit';
                document.getElementById('repairForm').submit();
            },

            submitDelete() {
                if (!confirm('确定删除此草稿？')) return;
                document.getElementById('formAction').value = 'delete';
                document.getElementById('repairForm').submit();
            }
        };
    }
    </script>
</x-app-layout>
