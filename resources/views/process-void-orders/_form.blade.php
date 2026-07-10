<form method="POST" action="{{ $order ? route('process-void-orders.update', $order) : route('process-void-orders.store') }}" enctype="multipart/form-data" id="processVoidForm">
    @csrf
    @if($order)
        @method('PUT')
    @endif
    <input type="hidden" name="_action" value="draft" id="processVoidAction">
    {{-- File is uploaded via AJAX parse; store the original file in a hidden input for final submission --}}
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
                const res = await fetch(base + '/process-void-orders/parse', {
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
                document.querySelector('[name=flow_start_time]').value = p.flow_start_time || '';
                document.querySelector('[name=company_name]').value = p.company_name || '';
                document.querySelector('[name=tax_no]').value = p.tax_no || '';
                document.querySelector('[name=process_name]').value = p.process_name || '';
                document.querySelector('[name=termination_reason]').value = p.termination_reason || '';
                document.querySelector('[name=submitter_sign]').value = p.submitter_sign || '';
                document.querySelector('[name=department_chief_sign]').value = p.department_chief_sign || '';
                this.fileName = data.source_file_name;

                // Store the actual file for final form submission
                const dt = new DataTransfer();
                dt.items.add(file);
                const realInput = document.getElementById('sourceDocReal');
                realInput.files = dt.files;
            } catch (e) {
                this.error = '网络错误，请重试';
            }
            this.loading = false;
        },
        onDrop(e) {
            this.dragover = false;
            const file = e.dataTransfer.files[0];
            this.handleFile(file);
        }
    }">
        <h3 class="text-base font-semibold text-gray-800 mb-4">上传 Word 模板</h3>

        {{-- Drag & drop zone --}}
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

        {{-- File info / error --}}
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

        {{-- Real file input (hidden, for form submission) --}}
        <input type="file" name="source_doc" id="sourceDocReal" accept=".docx" class="hidden">
        @error('source_doc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">提取信息（可修改）</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">科所名称</label>
                <input type="text" name="department" value="{{ old('department', $parsed['department'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">起流时间</label>
                <input type="text" name="flow_start_time" value="{{ old('flow_start_time', $parsed['flow_start_time'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">企业名称</label>
                <input type="text" name="company_name" value="{{ old('company_name', $parsed['company_name'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">税号</label>
                <input type="text" name="tax_no" value="{{ old('tax_no', $parsed['tax_no'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">流程名称</label>
                <textarea name="process_name" rows="2" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">{{ old('process_name', $parsed['process_name'] ?? '') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">终止原因</label>
                <textarea name="termination_reason" rows="2" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">{{ old('termination_reason', $parsed['termination_reason'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">提请人签字</label>
                <input type="text" name="submitter_sign" value="{{ old('submitter_sign', $parsed['submitter_sign'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">科所长签字</label>
                <input type="text" name="department_chief_sign" value="{{ old('department_chief_sign', $parsed['department_chief_sign'] ?? '') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-3">
        <a href="{{ route('process-void-orders.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
        <button type="button" onclick="document.getElementById('processVoidAction').value='draft';document.getElementById('processVoidForm').submit();" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
        <button type="button" onclick="document.getElementById('voidModal').classList.remove('hidden')" class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">提交作废</button>
    </div>
</form>

<div id="voidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b bg-red-50 border-red-100 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-red-700">提交作废信息</h3>
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">作废人 <span class="text-red-500">*</span></label>
                <input type="text" name="voided_by" form="processVoidForm" value="{{ old('voided_by') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-red-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">作废时间 <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="voided_at" form="processVoidForm" value="{{ old('voided_at', date('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-red-500" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="paper_submitted" form="processVoidForm" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500" {{ old('paper_submitted') ? 'checked' : '' }}>
                已提交纸质单据
            </label>
        </div>
        <div class="px-6 py-3 border-t bg-gray-50 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">取消</button>
            <button type="button" onclick="document.getElementById('processVoidAction').value='submit';document.getElementById('processVoidForm').submit();" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700">确认提交</button>
        </div>
    </div>
</div>
