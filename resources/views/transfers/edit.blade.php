<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">编辑调拨单</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $transferOrder->order_no ? $transferOrder->order_no : '草稿' }} · 待提交
                </p>
            </div>
            <a href="{{ route('transfers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</a>
        </div>
    </x-slot>

    <div x-data="transferEdit({{ json_encode($assets->keyBy('id')->toArray()) }}, {{ json_encode($changes) }})" x-init="init()" class="flex flex-col gap-3" style="min-height: calc(100vh - 220px);">
        <!-- 调拨原因 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 shrink-0">
            <label class="block text-sm font-medium text-gray-700 mb-2">调拨原因</label>
            <textarea x-model="reason" rows="2"
                      placeholder="请输入调拨原因（选填）..."
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>

        <!-- 资产编辑表格 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col flex-1">
            <div class="px-4 py-3 border-b bg-gray-50/50 flex items-center justify-between shrink-0">
                <span class="text-sm text-gray-600">共 <strong class="text-blue-600">{{ $assets->count() }}</strong> 项，已修改 <strong class="text-orange-600" x-text="modifiedCount"></strong> 项</span>
                <div class="flex items-center gap-3">
                    <div class="relative" @click.away="colMenuOpen = false">
                        <button @click="colMenuOpen = !colMenuOpen" class="flex items-center px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-white">
                            <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            列设置
                        </button>
                        <div x-show="colMenuOpen" x-cloak class="absolute right-0 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-lg p-3 max-h-80 overflow-y-auto" style="z-index: 99999;">
                            <template x-for="col in columns" :key="col.field">
                                <label class="flex items-center px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer text-sm">
                                    <input type="checkbox" x-model="col.visible" class="rounded border-gray-300 text-blue-600">
                                    <span class="ml-2 text-xs" x-text="col.label"></span>
                                    <span x-show="col.editable" class="text-blue-400 ml-0.5 text-[10px]">可编辑</span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <button @click="deleteSelected()" x-show="selected.length > 0" class="px-3 py-1.5 bg-red-500 text-white text-xs rounded-lg hover:bg-red-600">
                        移除选中（<span x-text="selected.length"></span>）
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50/50"><tr>
                        <th class="w-10 px-3 py-3"><input type="checkbox" @change="toggleAll" :checked="allChecked" class="rounded"></th>
                        <template x-for="col in columns" :key="col.field">
                            <th x-show="col.visible" class="px-3 py-3 text-left text-xs font-medium text-gray-500 whitespace-nowrap">
                                <span x-text="col.label"></span>
                            </th>
                        </template>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 whitespace-nowrap">修改状态</th>
                    </tr></thead>
                    <tbody>
                        @foreach($assets as $asset)
                        <tr class="hover:bg-gray-50/30" :class="isModified({{ $asset->id }}) ? 'bg-orange-50/50' : ''">
                            <td class="px-3 py-2.5"><input type="checkbox" :value="{{ $asset->id }}" x-model="selected" class="rounded"></td>

                            {{-- 只读列：服务器渲染不变 --}}
                            <td x-show="columns[0].visible" class="px-3 py-2.5 font-mono font-medium text-gray-800">{{ $asset->asset_code }}</td>
                            <td x-show="columns[1].visible" class="px-3 py-2.5 font-mono text-gray-500">{{ $asset->financial_code ?: '-' }}</td>
                            <td x-show="columns[2].visible" class="px-3 py-2.5">{{ $asset->name ?: '-' }}</td>

                            {{-- 可编辑列 --}}
                            @foreach(['department','room','ip','mac','sn','brand','model','category','user','status','remarks'] as $idx => $f)
                            @php
                                $labels = ['department'=>'部门','room'=>'房间号','ip'=>'IP地址','mac'=>'MAC地址','sn'=>'SN序列号','brand'=>'品牌','model'=>'规格型号','category'=>'类别','user'=>'使用人','status'=>'状态','remarks'=>'备注'];
                                $isDD = in_array($f, ['department', 'category', 'status']);
                                $colIdx = $idx + 3;
                            @endphp
                            <td x-show="columns[{{ $colIdx }}].visible"
                                class="px-1 py-1 relative"
                                :class="isModified({{ $asset->id }}, '{{ $f }}') ? 'bg-amber-50' : ''"
                                @if($isDD) @click="openDropdown({{ $asset->id }}, '{{ $f }}')"
                                @else @dblclick="startTextEdit({{ $asset->id }}, '{{ $f }}')" @endif
                            >
                                {{-- 显示模式（Alpine 动态渲染 x-text） --}}
                                <div x-show="!(editingId==={{ $asset->id }} && editingField==='{{ $f }}')"
                                     x-text="displayValue({{ $asset->id }}, '{{ $f }}')"
                                     class="px-2 py-1 rounded text-sm min-h-[28px] border cursor-pointer"
                                     :class="isModified({{ $asset->id }}, '{{ $f }}') ? 'border-amber-400 bg-amber-50' : 'border-transparent hover:bg-blue-50'"></div>

                                {{-- 下拉编辑模式 --}}
                                @if($isDD)
                                <div x-show="editingId==={{ $asset->id }} && editingField==='{{ $f }}'" x-cloak
                                     @click.away="cancelEdit()"
                                     class="absolute left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg w-64 max-h-56 flex flex-col"
                                     style="z-index: 99999;">
                                    <div class="p-2 border-b sticky top-0 bg-white z-10">
                                        <input type="text" x-model="editSearch"
                                               @input="filterDropdown('{{ $f }}')"
                                               @keydown.escape="cancelEdit()"
                                               @keydown.enter.prevent="selectFirstDropdown()"
                                               placeholder="搜索{{ $labels[$f] }}..."
                                               class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="overflow-y-auto max-h-44">
                                        <template x-for="opt in filteredOptions" :key="opt.code">
                                            <div @mousedown.prevent="selectDropdownOption({{ $asset->id }}, '{{ $f }}', opt)"
                                                 class="flex items-center px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm">
                                                <span class="text-xs">
                                                    <span class="font-mono text-gray-500" x-text="opt.code"></span>
                                                    <span class="mx-1.5 text-gray-300">|</span>
                                                    <span x-text="opt.name"></span>
                                                </span>
                                            </div>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-3 py-3 text-xs text-gray-400 text-center">无匹配选项</div>
                                    </div>
                                </div>

                                {{-- 文本编辑模式 --}}
                                @else
                                <div x-show="editingId==={{ $asset->id }} && editingField==='{{ $f }}'" x-cloak>
                                    <input type="text" x-model="changes[{{ $asset->id }}]['{{ $f }}']"
                                           @keydown.escape="cancelTextEdit({{ $asset->id }}, '{{ $f }}')"
                                           @keydown.enter="stopEdit()"
                                           @blur="stopEdit()"
                                           class="w-full border border-blue-400 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                                </div>
                                @endif
                            </td>
                            @endforeach

                            <td class="px-3 py-2.5">
                                <span x-show="isModified({{ $asset->id }})" class="text-xs text-orange-600 font-medium whitespace-nowrap">已修改</span>
                                <span x-show="!isModified({{ $asset->id }})" class="text-xs text-gray-400 whitespace-nowrap">未修改</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($assets->isEmpty())
                <div class="px-4 py-12 text-center text-gray-400">暂无资产数据</div>
                @endif
            </div>
        </div>

        <!-- 底部操作栏 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center justify-between shrink-0">
            <div class="text-sm text-gray-600">
                共 <strong>{{ $assets->count() }}</strong> 项，已修改 <strong class="text-orange-600" x-text="modifiedCount"></strong> 项
                <span x-show="modifiedCount === 0" class="text-red-500 ml-2">（点击部门/类别/状态下拉选择，双击其他列输入文本）</span>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('transfers.create', ['assets' => $assets->pluck('id')->implode(',')]) }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">← 返回重新选择</a>
                <form method="POST" action="{{ route('transfers.update', $transferOrder) }}" class="inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="_action" value="save">
                    <input type="hidden" name="changes" :value="JSON.stringify(changes)">
                    <input type="hidden" name="reason" :value="reason">
                    <button type="submit" class="px-6 py-2.5 border border-blue-200 text-blue-700 text-sm font-medium rounded-xl hover:bg-blue-50">暂存草稿</button>
                </form>
                <form method="POST" action="{{ route('transfers.update', $transferOrder) }}" class="inline" onsubmit="if(modifiedCount===0){alert('请先修改至少一项数据');return false;}">
                    @csrf @method('PUT')
                    <input type="hidden" name="_action" value="submit">
                    <input type="hidden" name="changes" :value="JSON.stringify(changes)">
                    <input type="hidden" name="reason" :value="reason">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">提交生效</button>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
<script>
function transferEdit(originalAssets, initialChanges) {
    return {
        changes: initialChanges || {},
        originalAssets: originalAssets || {},
        selected: [],
        editingId: null,
        editingField: null,
        colMenuOpen: false,
        reason: '{{ $reason }}',
        editSearch: '',
        filteredOptions: [],
        deptOptions: [],
        catOptions: [],
        statusOptions: [],

        columns: [
            { field: 'asset_code', label: '自有编号', visible: true, editable: false },
            { field: 'financial_code', label: '财务编码', visible: true, editable: false },
            { field: 'name', label: '资产名称', visible: true, editable: false },
            { field: 'department', label: '部门', visible: true, editable: true },
            { field: 'room', label: '房间号', visible: true, editable: true },
            { field: 'ip', label: 'IP地址', visible: false, editable: true },
            { field: 'mac', label: 'MAC地址', visible: false, editable: true },
            { field: 'sn', label: 'SN序列号', visible: false, editable: true },
            { field: 'brand', label: '品牌', visible: false, editable: true },
            { field: 'model', label: '规格型号', visible: false, editable: true },
            { field: 'category', label: '类别', visible: true, editable: true },
            { field: 'user', label: '使用人', visible: true, editable: true },
            { field: 'status', label: '状态', visible: true, editable: true },
            { field: 'remarks', label: '备注', visible: false, editable: true },
        ],

        async init() {
            try {
                const saved = localStorage.getItem('transferEditColumns');
                if (saved) { const d = JSON.parse(saved); this.columns.forEach(c => { if (d[c.field] !== undefined) c.visible = d[c.field]; }); }
            } catch(e) {}
            this.$watch('columns', () => {
                const data = {}; this.columns.forEach(c => data[c.field] = c.visible);
                localStorage.setItem('transferEditColumns', JSON.stringify(data));
            }, { deep: true });
            await this.loadOptions();
        },

        async loadOptions() {
            try {
                const [a,b,c] = await Promise.all([
                    fetch(APP_URL + '/api/depts'),
                    fetch(APP_URL + '/api/codes?type=category'),
                    fetch(APP_URL + '/api/codes?type=status'),
                ]);
                if (a.ok) this.deptOptions = await a.json();
                if (b.ok) this.catOptions = await b.json();
                if (c.ok) this.statusOptions = await c.json();
            } catch(e) {}
        },

        // ===== 显示值计算 =====
        translateCode(field, code) {
            const opts = field === 'department' ? this.deptOptions
                : field === 'category' ? this.catOptions
                : field === 'status' ? this.statusOptions : [];
            const f = opts.find(o => o.code === code);
            return f ? f.name : code;
        },

        displayValue(assetId, field) {
            // 如果有修改，显示修改后的值
            if (this.changes[assetId] && this.changes[assetId][field] !== undefined
                && this.changes[assetId][field] !== null && this.changes[assetId][field] !== '') {
                const val = this.changes[assetId][field];
                if (['department', 'category', 'status'].includes(field)) {
                    return this.translateCode(field, val) || val;
                }
                return val;
            }
            // 显示原始值
            const o = this.originalAssets[assetId];
            if (!o) return '-';
            if (field === 'department') return o.department_name || o.department || '-';
            if (field === 'category') return o.category_name || o.category || '-';
            if (field === 'status') return o.status_name || o.status || '-';
            return o[field] || '-';
        },

        // ===== 下拉选择 =====
        openDropdown(assetId, field) {
            this.editingId = assetId;
            this.editingField = field;
            this.editSearch = '';
            const all = field === 'department' ? this.deptOptions
                : field === 'category' ? this.catOptions : this.statusOptions;
            this.filteredOptions = all.slice(0, 50);
            this.$nextTick(() => {
                const inp = document.querySelector('[x-model="editSearch"]');
                if (inp) inp.focus();
            });
        },

        filterDropdown(field) {
            const all = field === 'department' ? this.deptOptions
                : field === 'category' ? this.catOptions : this.statusOptions;
            const q = this.editSearch.toLowerCase();
            this.filteredOptions = all.filter(o =>
                !q || o.code.toLowerCase().includes(q) || o.name.includes(this.editSearch)
            ).slice(0, 50);
        },

        selectDropdownOption(assetId, field, opt) {
            if (!this.changes[assetId]) this.changes[assetId] = {};
            this.changes[assetId][field] = opt.code;
            this.stopEdit();
        },

        selectFirstDropdown() {
            if (this.filteredOptions.length > 0) {
                this.selectDropdownOption(this.editingId, this.editingField, this.filteredOptions[0]);
            }
        },

        // ===== 文本编辑 =====
        startTextEdit(assetId, field) {
            this.editingId = assetId;
            this.editingField = field;
            if (!this.changes[assetId]) this.changes[assetId] = {};
            if (this.changes[assetId][field] === undefined || this.changes[assetId][field] === null) {
                const o = this.originalAssets[assetId];
                this.changes[assetId][field] = o ? (o[field] || '') : '';
            }
            this.$nextTick(() => {
                const inp = this.$el.querySelector('input[type="text"]');
                if (inp) { inp.focus(); inp.select(); }
            });
        },

        cancelTextEdit(assetId, field) {
            // 恢复原始值
            if (this.changes[assetId]) {
                const o = this.originalAssets[assetId];
                const orig = o ? (o[field] || '') : '';
                if (orig) {
                    this.changes[assetId][field] = orig;
                } else {
                    delete this.changes[assetId][field];
                    if (Object.keys(this.changes[assetId]).length === 0) delete this.changes[assetId];
                }
            }
            this.stopEdit();
        },

        // ===== 通用 =====
        cancelEdit() { this.stopEdit(); },
        stopEdit() {
            this.editingId = null;
            this.editingField = null;
            this.editSearch = '';
            this.filteredOptions = [];
        },

        get modifiedCount() {
            return Object.keys(this.changes).filter(id => {
                const c = this.changes[id];
                return c && Object.values(c).some(v => v !== undefined && v !== null && v !== '');
            }).length;
        },
        get allChecked() {
            const total = {{ $assets->count() }};
            return total > 0 && this.selected.length === total;
        },
        isModified(assetId, field) {
            if (!field) return !!this.changes[assetId] && Object.values(this.changes[assetId]).some(v => v !== undefined && v !== null && v !== '');
            return this.changes[assetId] && this.changes[assetId][field] !== undefined && this.changes[assetId][field] !== null && this.changes[assetId][field] !== '';
        },
        toggleAll(e) {
            this.selected = e.target.checked ? @json($assets->pluck('id')) : [];
        },
        deleteSelected() {
            this.selected.forEach(id => { delete this.changes[id]; });
            this.selected = [];
        },
    };
}
</script>
@endpush
</x-app-layout>
