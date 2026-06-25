<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">导出资产 CSV</h2>
                <p class="text-sm text-gray-500 mt-0.5">筛选并预览数据后再导出</p>
            </div>
            <a :href="exportUrl" href="{{ route('assets.exportCsv') }}"
               class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 shadow-sm">
                导出 CSV（<span x-text="total || '...'"></span> 条）
            </a>
        </div>
    </x-slot>

    <div x-data="exportPreview" x-init="init()" class="space-y-4">
        <!-- 筛选 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-3 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="search" @input="debounceSearch()" placeholder="搜索..."
                       class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <button @click="resetAll()" x-show="hasFilters" x-cloak
                        class="px-4 py-2 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50">重置</button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
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

        <!-- 数据预览 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50/50 flex items-center justify-between">
                <span class="text-sm text-gray-600">预览（共 <strong class="text-blue-600" x-text="total"></strong> 条匹配记录）</span>
                <span class="text-xs text-gray-400">CSV 将与下方列一致</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-xs">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-gray-500">自有编号</th>
                            <th class="px-3 py-2 text-left text-gray-500">财务编码</th>
                            <th class="px-3 py-2 text-left text-gray-500">资产名称</th>
                            <th class="px-3 py-2 text-left text-gray-500">部门</th>
                            <th class="px-3 py-2 text-left text-gray-500">房间号</th>
                            <th class="px-3 py-2 text-left text-gray-500">IP</th>
                            <th class="px-3 py-2 text-left text-gray-500">MAC</th>
                            <th class="px-3 py-2 text-left text-gray-500">SN</th>
                            <th class="px-3 py-2 text-left text-gray-500">品牌</th>
                            <th class="px-3 py-2 text-left text-gray-500">型号</th>
                            <th class="px-3 py-2 text-left text-gray-500">类别</th>
                            <th class="px-3 py-2 text-left text-gray-500">状态</th>
                            <th class="px-3 py-2 text-left text-gray-500">使用人</th>
                            <th class="px-3 py-2 text-left text-gray-500">备注</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="a in assets" :key="a.id">
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-3 py-1.5 font-mono text-gray-800" x-text="a.asset_code"></td>
                                <td class="px-3 py-1.5 text-gray-500" x-text="a.financial_code || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.name || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.department_name || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.room || '-'"></td>
                                <td class="px-3 py-1.5 font-mono text-gray-600" x-text="a.ip"></td>
                                <td class="px-3 py-1.5 font-mono text-gray-500" x-text="a.mac"></td>
                                <td class="px-3 py-1.5 text-gray-500" x-text="a.sn || '-'"></td>
                                <td class="px-3 py-1.5 text-gray-500" x-text="a.brand || '-'"></td>
                                <td class="px-3 py-1.5 text-gray-500" x-text="a.model || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.category_name || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.status_name || '-'"></td>
                                <td class="px-3 py-1.5" x-text="a.user || '-'"></td>
                                <td class="px-3 py-1.5 text-gray-400 truncate max-w-[120px]" x-text="a.remarks || '-'"></td>
                            </tr>
                        </template>
                        <tr x-show="assets.length===0"><td colspan="14" class="px-4 py-8 text-center text-gray-400">暂无匹配数据</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t flex items-center justify-between" x-show="lastPage > 1">
                <span class="text-xs text-gray-500">共 <span x-text="total"></span> 条</span>
                <div class="flex gap-1">
                    <button @click="goPage(page-1)" :disabled="page<=1" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30">‹</button>
                    <span class="px-3 py-1.5 text-xs" x-text="page+'/'+lastPage"></span>
                    <button @click="goPage(page+1)" :disabled="page>=lastPage" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30">›</button>
                </div>
            </div>
        </div>
    </div>
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('exportPreview', () => ({
        search: '',
        page: 1, lastPage: 1, total: 0,
        assets: [],
        timer: null,
        filterDefs: [
            { name: 'departments', label: '全部部门', open: false, values: [], options: [], search: '' },
            { name: 'statuses', label: '全部状态', open: false, values: [], options: [], search: '' },
            { name: 'categories', label: '全部类别', open: false, values: [], options: [], search: '' },
            { name: 'brands', label: '全部品牌', open: false, values: [], options: [], search: '' },
            { name: 'models', label: '全部型号', open: false, values: [], options: [], search: '' },
            { name: 'rooms', label: '全部房间', open: false, values: [], options: [], search: '' },
            { name: 'users', label: '全部使用人', open: false, values: [], options: [], search: '' },
        ],

        get hasFilters() { return this.search || this.filterDefs.some(f => f.values.length > 0); },

        get exportUrl() {
            const p = new URLSearchParams({ search: this.search });
            this.filterDefs.forEach(f => { if (f.values.length) f.values.forEach(v => p.append(f.name+'[]', v)); });
            return APP_URL + '/assets/export/csv?' + p.toString();
        },

        async init() {
            await this.loadFilterOptions();
            await this.load();
        },

        async loadFilterOptions() {
            try {
                const res = await fetch(APP_URL + '/assets/search?q=');
                if (!res.ok) return;
                const data = await res.json();
                const map = { departments: ['department','department_name'], statuses: ['status','status_name'], categories: ['category','category_name'], brands: ['brand','brand'], models: ['model','model'], rooms: ['room','room'], users: ['user','user'] };
                this.filterDefs.forEach(fd => {
                    const [col, nameCol] = map[fd.name];
                    const seen = new Set();
                    fd.options = [];
                    data.forEach(a => {
                        const c = a[col], n = a[nameCol] || c;
                        if (c && !seen.has(c)) { seen.add(c); fd.options.push({code:c, name:n}); }
                    });
                    fd.options.sort((a,b) => a.name.localeCompare(b.name, 'zh'));
                });
            } catch(e) {}
        },

        async load() {
            const params = new URLSearchParams({ page: this.page, search: this.search });
            this.filterDefs.forEach(fd => { if (fd.values.length) fd.values.forEach(v => params.append(fd.name+'[]', v)); });
            window.history.replaceState(null, '', window.location.pathname + '?' + params.toString());
            try {
                const res = await fetch(APP_URL + '/assets/json?' + params.toString());
                if (res.ok) { const d = await res.json(); this.assets = d.data; this.total = d.meta.total; this.lastPage = d.meta.last_page; }
            } catch(e) {}
        },

        debounceSearch() { clearTimeout(this.timer); this.timer = setTimeout(() => { this.page = 1; this.load(); }, 300); },
        onFilterChange() { this.page = 1; this.load(); },
        goPage(p) { if (p >= 1 && p <= this.lastPage) { this.page = p; this.load(); } },
        resetAll() { this.search = ''; this.filterDefs.forEach(f => { f.values = []; f.open = false; }); this.page = 1; this.load(); },
    }));
});
</script>
@endpush
</x-app-layout>
