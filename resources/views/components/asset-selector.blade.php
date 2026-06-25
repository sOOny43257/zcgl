@props(['triggerLabel' => '选择资产', 'multiple' => true, 'context' => 'default'])

<div x-data="assetSelector" x-init="context='{{ $context }}'; multiple={{ $multiple ? 'true' : 'false' }}"
     id="as-{{ $context }}" class="inline-block">
    <button type="button" @click="open = true; if(assets.length===0) load()"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">
        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ $triggerLabel }}
    </button>
    <span x-show="allSelected.length > 0" x-cloak
          class="ml-2 inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
        已选 <span x-text="allSelected.length"></span> 项
    </span>

    <!-- 居中抽屉 -->
    <div x-show="open" x-cloak class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 99999;" @click.self="open = false">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
                <h2 class="text-lg font-semibold">选择资产</h2>
                <button @click="open = false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-500 text-xl leading-none">&times;</button>
            </div>

            <div class="px-6 py-3 border-b bg-gray-50/50 shrink-0 flex gap-2">
                <input type="text" x-model="search" @input="debounceSearch()" placeholder="搜索编号、名称、IP..."
                       class="flex-1 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                <button @click="search=''; page=1; load()" x-show="search" class="px-3 py-2 text-xs text-red-500 hover:bg-red-50 rounded-xl">清除</button>
            </div>

            <div class="flex-1 overflow-auto px-6 py-2">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr>
                            <th class="w-10 px-2 py-3"><input type="checkbox" @change="togglePageAll($event)" :checked="pageChecked" class="rounded border-gray-300 text-blue-600"></th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">自有编号</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">名称</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">部门</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">IP</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">类别</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500">状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="a in assets" :key="a.id">
                            <tr class="hover:bg-blue-50/30 cursor-pointer" :class="isSel(a.id) ? 'bg-blue-50/50' : ''" @click="toggleOne(a)">
                                <td class="px-2 py-2" @click.stop><input type="checkbox" :checked="isSel(a.id)" @change="toggleOne(a)" class="rounded border-gray-300 text-blue-600"></td>
                                <td class="px-3 py-2 font-mono font-medium" x-text="a.asset_code"></td>
                                <td class="px-3 py-2 text-gray-700" x-text="a.name || '-'"></td>
                                <td class="px-3 py-2 text-gray-600" x-text="a.department || '-'"></td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-600" x-text="a.ip"></td>
                                <td class="px-3 py-2" x-text="a.category_name || a.category"></td>
                                <td class="px-3 py-2"><span class="px-1.5 py-0.5 rounded-full text-xs" :class="a.status==='ZY'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600'" x-text="a.status_name || a.status"></span></td>
                            </tr>
                        </template>
                        <tr x-show="assets.length===0"><td colspan="7" class="px-4 py-12 text-center text-gray-400">未找到匹配资产</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t flex items-center justify-between shrink-0" x-show="lastPage > 1">
                <span class="text-xs text-gray-500">共 <span x-text="total"></span> 条</span>
                <div class="flex gap-1">
                    <button @click="goPage(page-1)" :disabled="page<=1" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30">‹</button>
                    <span class="px-3 py-1.5 text-xs" x-text="page+'/'+lastPage"></span>
                    <button @click="goPage(page+1)" :disabled="page>=lastPage" class="px-3 py-1.5 border rounded-lg text-xs disabled:opacity-30">›</button>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50/80 shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <span class="text-sm shrink-0">已选 <strong class="text-blue-600" x-text="allSelected.length"></strong> 项</span>
                    <div class="flex flex-wrap gap-1 overflow-hidden max-h-8">
                        <template x-for="(a,i) in allSelected.slice(0,5)" :key="a.id">
                            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded-lg text-xs shrink-0">
                                <span class="font-mono" x-text="a.asset_code"></span>
                                <button @click.stop="removeOne(i)" class="ml-1 text-blue-500 hover:text-red-500">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0 ml-2">
                    <button @click="allSelected=[]" x-show="allSelected.length>0" class="px-4 py-2 border rounded-xl text-sm">清空</button>
                    <button @click="confirm()" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">
                        确定（<span x-text="allSelected.length"></span>）
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assetSelector', () => ({
        context: 'default',
        multiple: true,
        open: false,
        search: '',
        page: 1,
        lastPage: 1,
        total: 0,
        assets: [],
        allSelected: [],
        timer: null,

        get pageChecked() {
            return this.assets.length > 0 && this.assets.every(a => this.allSelected.some(s => s.id === a.id));
        },

        isSel(id) { return this.allSelected.some(a => a.id === id); },

        async load() {
            const params = new URLSearchParams({ page: this.page, search: this.search });
            try {
                const res = await fetch(APP_URL + '/assets/json?' + params.toString());
                if (res.ok) {
                    const d = await res.json();
                    this.assets = d.data;
                    this.total = d.meta.total;
                    this.lastPage = d.meta.last_page;
                }
            } catch(e) {}
        },

        debounceSearch() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.page = 1; this.load(); }, 300);
        },

        goPage(p) { if (p >= 1 && p <= this.lastPage) { this.page = p; this.load(); } },

        toggleOne(a) {
            const idx = this.allSelected.findIndex(s => s.id === a.id);
            if (idx >= 0) this.allSelected.splice(idx, 1);
            else { if (!this.multiple) this.allSelected = []; this.allSelected.push(a); }
        },

        togglePageAll(e) {
            if (e.target.checked) {
                this.assets.forEach(a => { if (!this.allSelected.some(s => s.id === a.id)) this.allSelected.push(a); });
            } else {
                const ids = this.assets.map(a => a.id);
                this.allSelected = this.allSelected.filter(a => !ids.includes(a.id));
            }
        },

        removeOne(i) { this.allSelected.splice(i, 1); },

        confirm() {
            this.$dispatch('asset-selected', {
                assets: this.allSelected,
                ids: this.allSelected.map(a => a.id),
                context: this.context
            });
            this.open = false;
        }
    }));
});
</script>
@endpush
@endonce
