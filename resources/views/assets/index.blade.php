<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold text-gray-800">资产管理</h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('assets.exportCsv', request()->query()) }}" class="inline-flex items-center px-3 py-2 border border-green-300 text-sm font-medium rounded-lg text-green-700 hover:bg-green-50">导出CSV</a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('assets.importForm') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">导入CSV</a>
                <a href="{{ route('assets.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">添加资产</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="assetIndex" x-init="init()" class="space-y-3">
        <!-- 搜索 + 筛选 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <input type="text" x-model="search" @input="debounceSearch()"
                           placeholder="实时搜索：输入编号、名称、IP、SN..."
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button @click="resetAll()" x-show="hasFilters" x-cloak
                        class="px-4 py-2.5 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50 whitespace-nowrap">
                    重置筛选
                </button>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <template x-for="fd in visibleFilters" :key="fd.name">
                    <div class="relative" @click.away="fd.open = false" style="flex: 0 0 calc(25% - 6px); min-width: 140px;">
                        <button type="button" @click="fd.open = !fd.open"
                                class="w-full flex items-center justify-between px-3 py-2.5 border rounded-xl text-sm"
                                :class="fd.values.length > 0 ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
                            <span class="truncate text-left text-xs" x-text="fd.values.length > 0 ? fd.values.length+' 项' : fd.label"></span>
                            <svg class="h-3.5 w-3.5 ml-1 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="fd.open" x-cloak class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-56 flex flex-col">
                            <div class="p-2 border-b sticky top-0 bg-white z-10">
                                <input type="text" x-model="fd.search" :placeholder="'搜索'+fd.label.replace('全部','')+'...'"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="overflow-y-auto max-h-44">
                                <template x-for="opt in fd.options.filter(o => !fd.search || o.name.includes(fd.search) || o.code.toLowerCase().includes(fd.search.toLowerCase()))" :key="opt.code">
                                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm">
                                        <input type="checkbox" :value="opt.code" x-model="fd.values" @change="onFilterChange()"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-xs">
                                            <span class="font-mono text-gray-500" x-text="opt.code"></span>
                                            <span class="mx-1.5 text-gray-300">|</span>
                                            <span x-text="opt.name"></span>
                                        </span>
                                    </label>
                                </template>
                                <div x-show="fd.options.filter(o => !fd.search || o.name.includes(fd.search) || o.code.toLowerCase().includes(fd.search.toLowerCase())).length === 0" class="px-3 py-3 text-xs text-gray-400 text-center">无匹配选项</div>
                            </div>
                        </div>
                    </div>
                </template>
                <button @click="showMoreFilters = !showMoreFilters"
                        class="px-3 py-2.5 border border-dashed border-gray-300 rounded-xl text-xs text-gray-500 hover:border-blue-400 hover:text-blue-600 whitespace-nowrap"
                        x-text="showMoreFilters ? '收起筛选' : '+ 更多筛选'"
                        style="flex: 0 0 auto;"></button>
            </div>
            <!-- 旧的 grid 删除（上面替代了） -->
            <div class="hidden">
                <template x-for="fd in filterDefs" :key="fd.name">
                    <div class="relative" @click.away="fd.open = false">
                        <button type="button" @click="fd.open = !fd.open"
                                class="w-full flex items-center justify-between px-3 py-2.5 border rounded-xl text-sm"
                                :class="fd.values.length > 0 ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
                            <span class="truncate text-left text-xs" x-text="fd.values.length > 0 ? fd.values.length+' 项' : fd.label"></span>
                            <svg class="h-3.5 w-3.5 ml-1 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="fd.open" x-cloak class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-56 flex flex-col">
                            <div class="p-2 border-b sticky top-0 bg-white z-10">
                                <input type="text" x-model="fd.search" :placeholder="'搜索'+fd.label.replace('全部','')+'...'"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="overflow-y-auto max-h-44">
                                <template x-for="opt in fd.options.filter(o => !fd.search || o.name.includes(fd.search) || o.code.toLowerCase().includes(fd.search.toLowerCase()))" :key="opt.code">
                                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm">
                                        <input type="checkbox" :value="opt.code" x-model="fd.values" @change="onFilterChange()"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-xs">
                                            <span class="font-mono text-gray-500" x-text="opt.code"></span>
                                            <span class="mx-1.5 text-gray-300">|</span>
                                            <span x-text="opt.name"></span>
                                        </span>
                                    </label>
                                </template>
                                <div x-show="fd.options.filter(o => !fd.search || o.name.includes(fd.search) || o.code.toLowerCase().includes(fd.search.toLowerCase())).length === 0" class="px-3 py-3 text-xs text-gray-400 text-center">无匹配选项</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 表格 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- 工具栏: 列设置 + 计数 -->
            <div class="px-4 py-2 border-b bg-gray-50/50 flex items-center justify-between">
                <span class="text-xs text-gray-500">共 <strong class="text-blue-600" x-text="total"></strong> 条</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-500">每页</span>
                    <div class="relative" @click.away="perPageMenuOpen = false">
                        <button @click="perPageMenuOpen = !perPageMenuOpen" class="flex items-center gap-1 px-2 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-white min-w-[52px] justify-between">
                            <span x-text="perPage"></span>
                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="perPageMenuOpen" x-cloak class="absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-2 space-y-1">
                            <template x-for="opt in perPageOptions" :key="opt">
                                <button @click="setPerPage(opt)" class="w-full text-left px-2 py-1.5 rounded-lg text-xs hover:bg-gray-50" :class="perPage === opt ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'" x-text="opt + ' 条/页'"></button>
                            </template>
                            <div class="border-t pt-1.5 mt-1">
                                <div class="flex items-center gap-1">
                                    <input type="number" x-model.number="customPerPage" @keydown.enter="applyCustomPerPage()" min="1" max="500" placeholder="自定义"
                                           class="w-16 border border-gray-200 rounded-lg px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <button @click="applyCustomPerPage()" class="px-2 py-1 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 whitespace-nowrap">确定</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative" @click.away="colMenuOpen = false">
                    <button @click="colMenuOpen = !colMenuOpen" class="flex items-center px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-white">
                        <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        列设置
                    </button>
                    <div x-show="colMenuOpen" x-cloak class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-3">
                        <template x-for="col in columns" :key="col.field">
                            <label class="flex items-center px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer text-sm">
                                <input type="checkbox" x-model="col.visible" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-xs" x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 w-10">#</th>
                            <template x-for="col in columns" :key="col.field">
                                <th x-show="col.visible" class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                                    @click="toggleSort(col)">
                                    <span x-text="col.label"></span>
                                    <span x-show="sortField === col.field" x-text="sortDir === 'asc' ? ' ↑' : ' ↓'" class="text-blue-500"></span>
                                </th>
                            </template>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(a, idx) in assets" :key="a.id">
                            <tr class="hover:bg-gray-50/50 text-sm">
                                <td class="px-3 py-2 text-center text-xs text-gray-400" x-text="(page-1)*perPage + idx + 1"></td>
                                <template x-for="col in columns" :key="col.field">
                                    <td x-show="col.visible" class="px-3 py-2">
                                        <template x-if="col.field === 'name'">
                                            <a :href="APP_URL + '/assets/' + a.id" class="text-blue-600 hover:underline" x-text="a[col.field] || '-'"></a>
                                        </template>
                                        <template x-if="col.field === 'asset_code'">
                                            <span class="font-mono font-medium text-gray-800" x-text="a.asset_code"></span>
                                        </template>
                                        <template x-if="col.field === 'financial_code'">
                                            <span class="font-mono text-gray-500" x-text="a.financial_code || '-'"></span>
                                        </template>
                                        <template x-if="col.field === 'ip' || col.field === 'mac'">
                                            <span class="font-mono text-xs text-gray-600" x-text="a[col.field] || '-'"></span>
                                        </template>
                                        <template x-if="col.field === 'category'">
                                            <span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700" x-text="a.category_name || a.category"></span>
                                        </template>
                                        <template x-if="col.field === 'status'">
                                            <span class="px-1.5 py-0.5 rounded-full text-xs" :class="a.status==='ZY'?'bg-green-100 text-green-700':a.status==='XZ'?'bg-yellow-100 text-yellow-700':a.status==='JIE'?'bg-purple-100 text-purple-700':'bg-gray-100 text-gray-600'" x-text="a.status_name || a.status"></span>
                                        </template>
                                        <template x-if="col.field === 'department'">
                                            <span class="text-gray-600" x-text="a.department_name || a.department || '-'"></span>
                                        </template>
                                        <template x-if="!['name','asset_code','financial_code','ip','mac','category','status','department'].includes(col.field)">
                                            <span class="text-gray-600" x-text="a[col.field] || '-'"></span>
                                        </template>
                                    </td>
                                </template>
                                <td class="px-3 py-2">
                                    <div class="flex gap-2 text-xs whitespace-nowrap">
                                        <a :href="APP_URL + '/assets/' + a.id" class="text-blue-600 hover:underline">查看</a>
                                        @if(auth()->user()->isAdmin())
                                        <a :href="APP_URL + '/assets/' + a.id + '/edit'" class="text-indigo-600 hover:underline">编辑</a>
                                        <a href="#" @click.prevent="deleteAsset(a.id)" class="text-red-500 hover:underline">删除</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="assets.length === 0 && !loading">
                            <td class="px-4 py-16 text-center text-gray-400" :colspan="visibleCols + 2">暂无匹配的资产数据</td>
                        </tr>
                        <tr x-show="loading">
                            <td class="px-4 py-8 text-center text-gray-400" :colspan="visibleCols + 2">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t flex items-center justify-between" x-show="total > 0">
                <span class="text-xs text-gray-500">共 <span x-text="total"></span> 条</span>
                <div class="flex gap-1" x-show="lastPage > 1">
                    <button @click="goPage(1)" :disabled="page <= 1" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30 hover:bg-gray-50">首页</button>
                    <button @click="goPage(page - 1)" :disabled="page <= 1" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30 hover:bg-gray-50">‹</button>
                    <span class="px-3 py-1.5 text-xs text-gray-600" x-text="page + ' / ' + lastPage"></span>
                    <button @click="goPage(page + 1)" :disabled="page >= lastPage" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30 hover:bg-gray-50">›</button>
                    <button @click="goPage(lastPage)" :disabled="page >= lastPage" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30 hover:bg-gray-50">末页</button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assetIndex', () => ({
        search: '',
        page: 1,
        lastPage: 1,
        total: 0,
        loading: false,
        assets: [],
        timer: null,
        sortField: 'id',
        sortDir: 'desc',
        colMenuOpen: false,
        perPage: 20,
        perPageOptions: [20, 50, 100, 200],
        perPageMenuOpen: false,
        customPerPage: null,

        showMoreFilters: false,
        filterDefs: [
            { name: 'departments', label: '全部部门', open: false, values: [], options: [], search: '', common: true },
            { name: 'statuses', label: '全部状态', open: false, values: [], options: [], search: '', common: true },
            { name: 'categories', label: '全部类别', open: false, values: [], options: [], search: '', common: true },
            { name: 'brands', label: '全部品牌', open: false, values: [], options: [], search: '', common: true },
            { name: 'models', label: '全部型号', open: false, values: [], options: [], search: '', common: false },
            { name: 'rooms', label: '全部房间', open: false, values: [], options: [], search: '', common: false },
            { name: 'users', label: '全部使用人', open: false, values: [], options: [], search: '', common: false },
        ],
        get visibleFilters() {
            return this.showMoreFilters ? this.filterDefs : this.filterDefs.filter(f => f.common);
        },

        columns: [
            { field: 'asset_code', label: '自有编号', visible: true },
            { field: 'financial_code', label: '财务编码', visible: false },
            { field: 'name', label: '资产名称', visible: true },
            { field: 'department', label: '部门', visible: true },
            { field: 'room', label: '房间号', visible: false },
            { field: 'ip', label: 'IP地址', visible: true },
            { field: 'mac', label: 'MAC地址', visible: false },
            { field: 'sn', label: 'SN序列号', visible: false },
            { field: 'brand', label: '品牌', visible: false },
            { field: 'model', label: '规格型号', visible: false },
            { field: 'category', label: '类别', visible: true },
            { field: 'status', label: '状态', visible: true },
            { field: 'user', label: '使用人', visible: true },
            { field: 'updated_at', label: '更新时间', visible: false },
        ],

        get hasFilters() {
            return this.search || this.filterDefs.some(f => f.values.length > 0);
        },

        get visibleCols() {
            return this.columns.filter(c => c.visible).length;
        },

        async init() {
            // 恢复列设置
            // 恢复每页条数
            try {
                const savedPerPage = localStorage.getItem('assetPerPage');
                if (savedPerPage) this.perPage = parseInt(savedPerPage) || 20;
            } catch(e) {}
            try {
                const saved = localStorage.getItem('assetColumns');
                if (saved) {
                    const data = JSON.parse(saved);
                    this.columns.forEach(c => { if (data[c.field] !== undefined) c.visible = data[c.field]; });
                }
            } catch(e) {}
            this.$watch('columns', () => {
                const data = {};
                this.columns.forEach(c => data[c.field] = c.visible);
                localStorage.setItem('assetColumns', JSON.stringify(data));
            }, { deep: true });

            // 加载筛选选项
            await this.loadFilterOptions();
            // 加载数据
            await this.load();
        },

        async loadFilterOptions() {
            // 部门/状态/类别：从数据字典加载全部选项
            const dictMap = {
                departments: APP_URL + '/api/depts',
                statuses: APP_URL + '/api/codes?type=status',
                categories: APP_URL + '/api/codes?type=category',
            };
            for (const fd of this.filterDefs) {
                if (!dictMap[fd.name]) continue;
                try {
                    const res = await fetch(dictMap[fd.name]);
                    if (res.ok) {
                        fd.options = await res.json();
                        fd.options.sort((a,b) => a.name.localeCompare(b.name, 'zh'));
                    }
                } catch(e) {}
            }
            // 品牌/型号/房间/使用人：从资产数据推断
            try {
                const res = await fetch(APP_URL + '/assets/search?q=');
                if (!res.ok) return;
                const data = await res.json();
                const fieldMap = { brands: ['brand','brand'], models: ['model','model'], rooms: ['room','room'], users: ['user','user'] };
                this.filterDefs.forEach(fd => {
                    if (!fieldMap[fd.name]) return;
                    const [col, nameCol] = fieldMap[fd.name];
                    const seen = new Set();
                    fd.options = [];
                    data.forEach(a => {
                        const code = a[col];
                        const name = a[nameCol] || code;
                        if (code && !seen.has(code)) {
                            seen.add(code);
                            fd.options.push({ code, name });
                        }
                    });
                    fd.options.sort((a,b) => a.name.localeCompare(b.name, 'zh'));
                });
            } catch(e) {}
        },

        async load() {
            this.loading = true;
            const params = new URLSearchParams({ page: this.page, search: this.search, sort: this.sortField, direction: this.sortDir, per_page: this.perPage });
            this.filterDefs.forEach(fd => {
                if (fd.values.length) fd.values.forEach(v => params.append(fd.name + '[]', v));
            });

            window.history.replaceState(null, '', window.location.pathname + '?' + params.toString());

            try {
                const res = await fetch(APP_URL + '/assets/json?' + params.toString());
                if (res.ok) {
                    const d = await res.json();
                    this.assets = d.data;
                    this.total = d.meta.total;
                    this.lastPage = d.meta.last_page;
                }
            } catch(e) { this.assets = []; }
            this.loading = false;
        },

        debounceSearch() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.page = 1; this.load(); }, 300);
        },

        onFilterChange() {
            this.page = 1;
            this.load();
        },

        toggleSort(col) {
            if (this.sortField === col.field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = col.field;
                this.sortDir = 'asc';
            }
            this.load();
        },

        goPage(p) {
            if (p >= 1 && p <= this.lastPage) { this.page = p; this.load(); }
        },

        setPerPage(val) {
            this.perPage = val;
            this.page = 1;
            this.perPageMenuOpen = false;
            this.savePerPage();
            this.load();
        },

        applyCustomPerPage() {
            if (this.customPerPage && this.customPerPage >= 1 && this.customPerPage <= 500) {
                this.perPage = this.customPerPage;
                this.page = 1;
                this.perPageMenuOpen = false;
                this.savePerPage();
                this.load();
            } else {
                this.customPerPage = null;
            }
        },

        savePerPage() {
            localStorage.setItem('assetPerPage', this.perPage);
        },
        resetAll() {
            this.search = '';
            this.filterDefs.forEach(f => { f.values = []; f.open = false; });
            this.page = 1;
            this.load();
        },

        async deleteAsset(id) {
            if (!confirm('确定删除此资产？')) return;
            try {
                await fetch(APP_URL + '/assets/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                this.load();
            } catch(e) {}
        },
    }));
});
</script>
@endpush
</x-app-layout>
