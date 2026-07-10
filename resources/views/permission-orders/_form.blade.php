<form method="POST" action="{{ $order ? route('permission-orders.update', $order) : route('permission-orders.store') }}" enctype="multipart/form-data" id="permissionForm">
    @csrf
    @if($order)
        @method('PUT')
    @endif
    <input type="hidden" name="_action" value="draft" id="permissionAction">
    <input type="hidden" name="items_json" id="itemsJson" value="{{ old('items_json', json_encode($parsed['items'] ?? [], JSON_UNESCAPED_UNICODE)) }}">
    <input type="hidden" name="source_doc_hidden" id="sourceDocHidden" value="1">

    <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="{
        fileName: '{{ $order->source_file_name ?? '' }}',
        loading: false,
        error: '',
        dragover: false,
        async handleFile(file) {
            if (!file) return;
            if (!file.name.toLowerCase().endsWith('.docx')) {
                this.error = '仅支持 .docx 格式的 Word 文件';
                return;
            }
            this.error = '';
            this.loading = true;
            this.fileName = file.name;

            const fd = new FormData();
            fd.append('source_doc', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

            try {
                const base = (window.APP_URL || '').replace(/\/+$/, '');
                const res = await fetch(base + '/permission-orders/parse', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: fd,
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.error || '解析失败，请重试';
                    this.loading = false;
                    return;
                }
                const p = data.parsed;
                document.querySelector('[name=department]').value = p.department || '';
                document.querySelector('[name=fill_date]').value = p.fill_date || '';

                // Populate items rows
                if (p.items && p.items.length > 0) {
                    window.__permissionRows = p.items;
                    document.getElementById('itemsJson').value = JSON.stringify(p.items);
                    if (typeof renderRows === 'function') renderRows();
                }

                this.fileName = data.source_file_name;

                const dt = new DataTransfer();
                dt.items.add(file);
                document.getElementById('sourceDocReal').files = dt.files;
            } catch (e) {
                this.error = '网络错误，请重试';
            }
            this.loading = false;
        },
        onDrop(e) {
            this.dragover = false;
            this.handleFile(e.dataTransfer.files[0]);
        }
    }">
        <h3 class="text-base font-semibold text-gray-800 mb-4">上传 Word 模板</h3>

        <div class="border-2 border-dashed rounded-xl p-8 text-center transition-colors duration-200 cursor-pointer mb-4"
             :class="dragover ? 'border-blue-400 bg-blue-50' : 'border-gray-300 hover:border-blue-300 hover:bg-gray-50'"
             @dragover.prevent="dragover = true"
             @dragleave.prevent="dragover = false"
             @drop.prevent="onDrop($event)"
             @click="$refs.fileInput.click()">
            <input type="file" x-ref="fileInput" class="hidden" accept=".docx"
                   @change="handleFile($event.target.files[0])">
            <div x-show="!loading">
                <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-sm text-gray-600 mb-1">将 .docx 文件拖到此处，或 <span class="text-blue-600 font-medium">点击选择</span></p>
                <p class="text-xs text-gray-400">上传后自动提取表格信息，可手动修改</p>
            </div>
            <div x-show="loading" class="flex items-center justify-center gap-3">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="text-sm text-blue-600 font-medium">正在解析文档...</span>
            </div>
        </div>

        <div x-show="error" class="mb-3 px-4 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="error"></span>
        </div>
        <div x-show="fileName && !error && !loading" class="mb-3 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700 flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>已上传：<strong x-text="fileName"></strong>，信息已自动提取，可修改后保存</span>
        </div>
        @if($order && $order->source_file_name)
        <p class="text-xs text-gray-500 mb-2">当前文件：{{ $order->source_file_name }}（重新上传将覆盖）</p>
        @endif

        <input type="file" name="source_doc" id="sourceDocReal" accept=".docx" class="hidden">
        @error('source_doc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- 基本信息 --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">基本信息</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">填报部门</label>
                <input type="text" name="department" value="{{ old('department', $parsed['department'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">填写日期</label>
                <input type="text" name="fill_date" value="{{ old('fill_date', $parsed['fill_date'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    {{-- 权限调整明细 --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="{
        rows: JSON.parse(document.getElementById('itemsJson').value || '[]'),
        addRow() {
            this.rows.push({ names: '', business_system: '', original_position: '', added_position: '', removed_position: '' });
            this.syncJson();
        },
        removeRow(idx) {
            this.rows.splice(idx, 1);
            this.syncJson();
        },
        syncJson() {
            document.getElementById('itemsJson').value = JSON.stringify(this.rows);
        },
        updateField(idx, field, val) {
            this.rows[idx][field] = val;
            this.syncJson();
        }
    }" x-init="$nextTick(() => { window.renderRows = () => { rows = JSON.parse(document.getElementById('itemsJson').value || '[]'); }; })">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800">权限调整明细（可修改）</h3>
            <button type="button" @click="addRow()" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                添加行
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase w-8">#</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:160px">姓名</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:140px">涉及业务系统</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:120px">原岗位</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:160px">增加岗位</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:160px">减少岗位</th>
                        <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase w-16">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <template x-if="rows.length === 0">
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">暂无明细，点击"添加行"或上传 Word 文档自动提取</td></tr>
                    </template>
                    <template x-for="(row, idx) in rows" :key="idx">
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                            <td class="px-2 py-1.5">
                                <input type="text" :value="row.names" @input="updateField(idx, 'names', $event.target.value)" class="w-full border border-gray-200 rounded-lg py-1.5 px-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="多个人员用分号分隔">
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="text" :value="row.business_system" @input="updateField(idx, 'business_system', $event.target.value)" class="w-full border border-gray-200 rounded-lg py-1.5 px-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="text" :value="row.original_position" @input="updateField(idx, 'original_position', $event.target.value)" class="w-full border border-gray-200 rounded-lg py-1.5 px-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </td>
                            <td class="px-2 py-1.5">
                                <textarea :value="row.added_position" @input="updateField(idx, 'added_position', $event.target.value)" rows="2" class="w-full border border-gray-200 rounded-lg py-1.5 px-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                            </td>
                            <td class="px-2 py-1.5">
                                <textarea :value="row.removed_position" @input="updateField(idx, 'removed_position', $event.target.value)" rows="2" class="w-full border border-gray-200 rounded-lg py-1.5 px-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" @click="removeRow(idx)" class="text-red-400 hover:text-red-600 p-1" title="删除此行">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end space-x-3">
        <a href="{{ route('permission-orders.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
        <button type="button" onclick="document.getElementById('permissionAction').value='draft';document.getElementById('permissionForm').submit();" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
        <button type="button" onclick="document.getElementById('voidModal').classList.remove('hidden')" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">提交</button>
    </div>
</form>

<div id="voidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b bg-blue-50 border-blue-100 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-blue-700">提交权限单信息</h3>
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">修改人 <span class="text-red-500">*</span></label>
                <input type="text" name="voided_by" form="permissionForm" value="{{ old('voided_by') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">修改时间 <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="voided_at" form="permissionForm" value="{{ old('voided_at', date('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="paper_submitted" form="permissionForm" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ old('paper_submitted') ? 'checked' : '' }}>
                已提交纸质单据
            </label>
        </div>
        <div class="px-6 py-3 border-t bg-gray-50 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">取消</button>
            <button type="button" onclick="document.getElementById('permissionAction').value='submit';document.getElementById('permissionForm').submit();" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">确认提交</button>
        </div>
    </div>
</div>
