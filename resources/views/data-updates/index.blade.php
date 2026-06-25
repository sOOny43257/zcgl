<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">数据更新</h2>
                <p class="text-sm text-gray-500 mt-0.5">来自客户端脚本提交的资产数据，请验证后入库</p>
            </div>
            <span class="text-sm text-gray-400">{{ $submissions->count() }} 条待处理</span>
        </div>
    </x-slot>

    <div x-data="dataUpdates()" class="space-y-4">
        @if($submissions->isEmpty())
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-12 text-center text-gray-400">
            <svg class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-lg">暂无待处理数据</p>
            <p class="text-sm mt-1">客户端脚本提交数据后将在此显示</p>
        </div>
        @else
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-2 py-3 text-center w-8">#</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">SN</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">MAC</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">IP</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">姓名</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">部门</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">房间</th>
                            <th class="px-2 py-3 text-left text-xs text-gray-500">来源</th>
                            <th class="px-2 py-3 text-center text-xs text-gray-500 w-20">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($submissions as $s)
                        @php
                        $deptName = \App\Models\Asset::translateDept($s->department);
                        @endphp
                        <tr x-data="rowState({{ $s->id }}, {{ empty($s->errors) ? 'true' : 'false' }})"
                            :class="submitted ? 'bg-emerald-50/50' : (allValid ? '' : 'bg-red-50/30')"
                            class="hover:bg-gray-50/30" id="row-{{ $s->id }}">
                            <td class="px-2 py-2 text-center">
                                <span x-show="submitted" class="text-emerald-500 text-lg">✅</span>
                                <span x-show="!submitted && allValid" class="text-emerald-500 text-lg">✅</span>
                                <span x-show="!submitted && !allValid" class="text-red-500 text-lg">❌</span>
                            </td>
                            @foreach(['sn','mac','ip','name','department','room'] as $f)
                            @php
                            $hasError = isset($s->errors[$f]);
                            $sug = $s->suggestions[$f] ?? null;
                            // 部门字段：显示中文名，存储编码
                            if ($f === 'department') {
                                $display = $deptName;
                                $code = $s->department ?: '';
                            } else {
                                $display = $s->$f ?: '';
                                $code = $display;
                            }
                            @endphp
                            <td class="px-1 py-1">
                                <div x-data="editableCell({{ $s->id }}, '{{ $f }}', '{{ $code }}', '{{ $display }}', {{ $hasError ? 'true' : 'false' }}, {{ $sug ? json_encode($sug) : 'null' }})"
                                     @cell-updated="onCellUpdate($event.detail)"
                                     class="relative" @click.away="editing=false">
                                    <!-- 显示模式 -->
                                    <div @click="startEdit()" x-show="!editing"
                                         class="cursor-pointer rounded-md px-2 py-1 text-sm min-h-[28px]"
                                         :class="hasError ? 'bg-red-50 border border-red-300' : 'hover:bg-gray-50 border border-transparent'">
                                        <span x-text="displayValue || value || '-'"></span>
                                    </div>
                                    <!-- 编辑模式：搜索下拉 -->
                                    <div x-show="editing" x-cloak class="relative">
                                        <input type="text" x-model="search" @input="filter()" @keydown.escape="editing=false"
                                               class="w-full border border-blue-400 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-blue-300"
                                               :placeholder="'搜索...'">
                                        <div x-show="filtered.length > 0" class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-40 overflow-y-auto">
                                            <template x-for="opt in filtered" :key="opt.code">
                                                <div @click="select(opt)"
                                                     class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm flex items-center justify-between"
                                                     :class="opt.code === value ? 'bg-blue-50' : ''">
                                                    <span>
                                                        <span class="font-mono font-medium text-gray-800" x-text="opt.code"></span>
                                                        <span class="mx-2 text-gray-300">|</span>
                                                        <span class="text-gray-600" x-text="opt.name"></span>
                                                    </span>
                                                    <svg x-show="opt.code === value" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- 可点击的建议提示 -->
                                    <div x-show="!editing && suggestion && suggestion.code !== value"
                                         @click.stop="value=suggestion.code; suggestion=null; hasError=false; save()"
                                         class="absolute -top-8 left-0 bg-white border border-amber-300 rounded-lg px-2 py-1 text-xs shadow-lg whitespace-nowrap z-10 cursor-pointer hover:bg-amber-50 transition-colors">
                                        💡 <span class="font-mono text-blue-600" x-text="suggestion.code"></span>
                                        <span class="text-gray-500" x-text="suggestion.name"></span>
                                        <span class="text-amber-500 ml-1">点击填充</span>
                                    </div>
                                </div>
                            </td>
                            @endforeach
                            <td class="px-2 py-2 text-xs text-gray-500">{{ $s->created_at->format('m-d H:i') }}</td>
                            <td class="px-2 py-2 text-center">
                                <div class="flex gap-1 justify-center">
                                    <button x-show="!submitted" @click="submitRow({{ $s->id }}); markSubmitted()"
                                            class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">提交</button>
                                    <button x-show="submitted" disabled
                                            class="px-3 py-1.5 bg-emerald-500 text-white text-xs rounded-lg cursor-default">已提交</button>
                                    <button x-show="!submitted" @click="deleteRow({{ $s->id }})"
                                            class="px-2 py-1.5 text-red-500 text-xs hover:text-red-700">✕</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 右下角提交全部 -->
        <div class="flex justify-end">
            <button @click="submitAll()" class="px-6 py-3 bg-blue-600 text-white text-sm font-bold rounded-2xl shadow-lg hover:bg-blue-700 transition-colors">
                提交全部合法数据
            </button>
        </div>
        @endif

        <!-- 结果弹窗 -->
        <div x-show="result" x-cloak class="fixed inset-0 flex items-center justify-center" style="z-index: 99999;" @click.self="result=null">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-3xl p-8 shadow-2xl max-w-md w-full text-center">
                <div class="text-4xl mb-4" x-text="result.success ? '✅' : '❌'"></div>
                <p class="text-lg font-semibold text-gray-800 mb-2" x-text="result.message"></p>
                <p class="text-sm text-gray-500" x-show="result.detail" x-text="result.detail"></p>
                <button @click="result=null; location.reload()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700">确定</button>
            </div>
        </div>
    </div>
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editableCell', (id, field, initialCode, initialDisplay, hasErr, sug) => ({
        id, field,
        value: initialCode,
        displayValue: initialDisplay || initialCode,
        hasError: hasErr,
        suggestion: sug,
        editing: false,
        search: '',
        filtered: [],
        allOptions: [],

        async startEdit() {
            this.editing = true;
            this.search = this.value || '';
            // 加载所有选项（仅 department/category/status 字段）
            const typeMap = { department: 'department', category: 'category', status: 'status' };
            const type = typeMap[this.field];
            if (type && this.allOptions.length === 0) {
                try {
                    const res = await fetch(APP_URL + '/api/codes?type=' + type);
                    if (res.ok) this.allOptions = await res.json();
                } catch(e) {}
            }
            this.filter();
        },

        filter() {
            const q = (this.search || '').toLowerCase();
            this.filtered = this.allOptions.filter(o =>
                o.code.toLowerCase().includes(q) || o.name.includes(this.search || '')
            ).slice(0, 20);
        },

        select(opt) {
            this.value = opt.code;
            this.displayValue = opt.name;
            this.search = '';
            this.editing = false;
            this.save();
        },

        async save() {
            try {
                const res = await fetch(APP_URL + '/data-updates/field', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ id: this.id, field: this.field, value: this.value })
                });
                if (res.ok) {
                    this.hasError = false;
                    this.suggestion = null;
                    const typeMap = { department: 'department', category: 'category', status: 'status' };
                    if (typeMap[this.field]) {
                        const sRes = await fetch(APP_URL + '/data-updates/suggest', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ field: this.field, value: this.value })
                        });
                        if (sRes.ok) {
                            const s = await sRes.json();
                            this.suggestion = s.suggestion;
                            if (!s.match) this.hasError = true;
                        }
                    }
                    // 通知行状态更新
                    this.$dispatch('cell-updated', { hasError: this.hasError });
                }
            } catch(e) {}
        }
    }));

    Alpine.data('rowState', (id, initialValid) => ({
        submitted: false,
        allValid: initialValid,
        onCellUpdate(detail) {
            // 简化：如果有错误则 invalid
            if (detail.hasError) this.allValid = false;
        },
        markSubmitted() {
            this.submitted = true;
            this.allValid = true;
        }
    }));
});

function dataUpdates() {
    return {
        result: null,

        async submitRow(id) {
            if (!confirm('确定提交此条数据？')) return;
            try {
                const res = await fetch(APP_URL + '/data-updates/submit/' + id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.result = data;
                if (data.success) {
                    const row = document.getElementById('row-' + id);
                    if (row) row.remove();
                }
            } catch(e) {
                this.result = { success: false, message: '提交失败' };
            }
        },

        async submitAll() {
            if (!confirm('确定提交所有合法数据？')) return;
            try {
                const res = await fetch(APP_URL + '/data-updates/submit-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                });
                location.reload();
            } catch(e) {}
        },

        async deleteRow(id) {
            if (!confirm('确定删除此行？')) return;
            try {
                await fetch(APP_URL + '/data-updates/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const row = document.getElementById('row-' + id);
                if (row) row.remove();
            } catch(e) {}
        }
    };
}
</script>
@endpush
</x-app-layout>
