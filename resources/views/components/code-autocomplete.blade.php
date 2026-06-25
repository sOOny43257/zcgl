@props(['name' => 'category', 'type' => 'category', 'value' => '', 'placeholder' => '输入编码或名称搜索...'])

<div x-data="codeAutocomplete('{{ $name }}', '{{ $type }}', '{{ $value }}')" @click.away="open = false" class="relative">
    <input type="hidden" :name="fieldName" :value="selectedCode">
    <input type="text"
           x-model="search"
           @input="filter()"
           @focus="open = true; if(results.length===0) filter()"
           @click="open = true"
           :placeholder="placeholder"
           autocomplete="off"
           class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
    <div x-show="open && results.length > 0" x-cloak
         class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-52 overflow-y-auto">
        <template x-for="d in results" :key="d.code">
            <div @click="select(d)"
                 class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer flex items-center justify-between text-sm"
                 :class="d.code === selectedCode ? 'bg-blue-50' : ''">
                <span>
                    <span class="font-mono font-medium text-gray-800" x-text="d.code"></span>
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="text-gray-700" x-text="d.name"></span>
                </span>
                <svg x-show="d.code === selectedCode" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('codeAutocomplete', (fieldName, type, initialCode) => ({
        fieldName,
        type,
        selectedCode: initialCode || '',
        search: '',
        open: false,
        results: [],
        allItems: [],
        placeholder: {category:'输入编码或名称搜索类别...',status:'输入编码或名称搜索状态...',department:'输入拼音简写或中文搜索部门...'}[type] || '搜索...',

        async init() {
            const urlMap = { department: APP_URL + '/api/depts', category: APP_URL + '/api/codes?type=category', status: APP_URL + '/api/codes?type=status' };
            try {
                const res = await fetch(urlMap[type] || APP_URL + '/api/codes?type=' + type);
                if (res.ok) this.allItems = await res.json();
                if (this.selectedCode) {
                    const found = this.allItems.find(d => d.code === this.selectedCode);
                    if (found) this.search = found.name;
                }
            } catch(e) {}
        },

        filter() {
            const q = this.search.toLowerCase();
            this.results = this.allItems.filter(d =>
                d.code.toLowerCase().includes(q) || d.name.includes(this.search)
            ).slice(0, 20);
            if (this.results.length > 0) this.open = true;
        },

        select(d) {
            this.selectedCode = d.code;
            this.search = d.name;
            this.open = false;
        }
    }));
});
</script>
@endpush
@endonce
