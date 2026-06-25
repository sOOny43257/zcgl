@props(['name' => 'asset_ids', 'multiple' => true, 'label' => '选择设备', 'placeholder' => '输入自有编号、资产名称或IP搜索...'])

<div x-data="assetPicker('{{ $name }}', {{ $multiple ? 'true' : 'false' }})" class="asset-picker">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }} <span class="text-red-500">*</span>
        @if($multiple)
        <span class="text-gray-400 text-xs font-normal ml-2">支持搜索和多选</span>
        @endif
    </label>

    <!-- 搜索输入 -->
    <div class="relative mb-2">
        <input type="text" x-model="search" @input="searchAssets()"
               placeholder="{{ $placeholder }}"
               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>

    <!-- 搜索结果 -->
    <div x-show="search.length > 0" class="border border-gray-200 rounded-lg max-h-60 overflow-y-auto mb-3">
        <template x-for="asset in searchResults" :key="asset.id">
            <label class="flex items-center px-3 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                   :class="isSelected(asset.id) ? 'bg-blue-50' : ''">
                <input type="checkbox" :checked="isSelected(asset.id)"
                       @change="toggleAsset(asset)"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <div class="ml-3 flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-mono font-bold text-gray-900" x-text="asset.asset_code"></span>
                        <span class="text-sm text-gray-700 truncate" x-text="asset.name || '(未命名)'"></span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium shrink-0"
                              :class="asset.status === '在用' ? 'bg-green-100 text-green-800' : asset.status === '借用' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800'"
                              x-text="asset.status"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        IP: <span x-text="asset.ip"></span>
                        <span class="mx-1">|</span>
                        部门: <span x-text="asset.department || '-'"></span>
                    </div>
                </div>
            </label>
        </template>
        <div x-show="searchResults.length === 0" class="px-4 py-6 text-center text-gray-400 text-sm">
            未找到匹配的设备
        </div>
    </div>

    <!-- 已选资产标签 -->
    <div class="flex flex-wrap gap-2" x-show="selectedAssets.length > 0">
        <template x-for="(asset, idx) in selectedAssets" :key="asset.id">
            <span class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg text-sm">
                <span class="font-mono font-bold" x-text="asset.asset_code"></span>
                <span class="mx-1.5 text-blue-400">|</span>
                <span x-text="asset.name || '(未命名)'"></span>
                <button type="button" @click="removeAsset(idx)" class="ml-2 text-blue-500 hover:text-red-500 font-bold text-lg leading-none">&times;</button>
                <input type="hidden" name="{{ $name }}[]" :value="asset.id">
            </span>
        </template>
    </div>
    <p x-show="selectedAssets.length === 0" class="text-xs text-gray-400 mt-1">请在上方搜索并选择设备</p>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assetPicker', (fieldName, multiple) => ({
        fieldName: fieldName,
        multiple: multiple,
        search: '',
        searchResults: [],
        selectedAssets: [],
        searchTimer: null,

        async searchAssets() {
            clearTimeout(this.searchTimer);
            if (this.search.length < 1) { this.searchResults = []; return; }
            this.searchTimer = setTimeout(async () => {
                try {
                    const url = '{{ route('assets.searchJson') }}?q=' + encodeURIComponent(this.search);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        // 过滤掉已选中的资产
                        this.searchResults = data.filter(a => !this.selectedAssets.some(s => s.id === a.id));
                    }
                } catch(e) { this.searchResults = []; }
            }, 300);
        },

        isSelected(id) {
            return this.selectedAssets.some(a => a.id === id);
        },

        toggleAsset(asset) {
            if (this.isSelected(asset.id)) {
                this.selectedAssets = this.selectedAssets.filter(a => a.id !== asset.id);
            } else {
                if (!this.multiple) {
                    this.selectedAssets = [];
                }
                this.selectedAssets.push(asset);
            }
            this.search = '';
            this.searchResults = [];
        },

        removeAsset(idx) {
            this.selectedAssets.splice(idx, 1);
        }
    }));
});
</script>
@endpush
@endonce
