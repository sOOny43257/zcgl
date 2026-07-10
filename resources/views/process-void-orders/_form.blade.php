<form method="POST" action="{{ $order ? route('process-void-orders.update', $order) : route('process-void-orders.store') }}" enctype="multipart/form-data" id="processVoidForm">
    @csrf
    @if($order)
        @method('PUT')
    @endif
    <input type="hidden" name="_action" value="draft" id="processVoidAction">

    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-4">上传 Word 模板</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">流程单文件 <span class="text-red-500">*</span></label>
                <input type="file" name="source_doc" accept=".docx" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('source_doc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @if($order && $order->source_file_name)
                <p class="mt-2 text-xs text-gray-500">已上传：{{ $order->source_file_name }}（重新上传将覆盖）</p>
                @endif
            </div>
            <div class="text-sm text-gray-500 leading-6">
                <p>仅支持 <strong>.docx</strong> 文件，系统会自动提取第一张表格中的字段。</p>
                <p>若模板不规范导致解析失败，页面会提示重新上传。</p>
            </div>
        </div>
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
