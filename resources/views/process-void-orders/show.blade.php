<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">流程单详情</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $processVoidOrder->order_no ?: '草稿' }} — {{ $processVoidOrder->statusLabel() }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($processVoidOrder->source_doc_path)
                <a href="{{ route('process-void-orders.download', $processVoidOrder) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    下载原件
                </a>
                @endif
                @if($processVoidOrder->isDraft())
                <a href="{{ route('process-void-orders.edit', $processVoidOrder) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">编辑</a>
                @endif
                <a href="{{ route('process-void-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        {{-- 基本信息 --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">基本信息</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                <div>
                    <dt class="text-xs text-gray-500">单号</dt>
                    <dd class="text-sm font-mono font-medium text-blue-700">{{ $processVoidOrder->order_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">状态</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $processVoidOrder->isDraft() ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600' }}">
                            {{ $processVoidOrder->statusLabel() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">科所名称</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->department ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">起流时间</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->flow_start_time ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-gray-500">企业名称</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->company_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">税号</dt>
                    <dd class="text-sm font-mono text-gray-800">{{ $processVoidOrder->tax_no ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-gray-500">流程名称</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->process_name ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-gray-500">终止原因</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->termination_reason ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">提请人签字</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->submitter_sign ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">科所长签字</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->department_chief_sign ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- 作废信息（仅已作废状态显示） --}}
        @if($processVoidOrder->isVoided())
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">作废信息</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3">
                <div>
                    <dt class="text-xs text-gray-500">作废人</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->voided_by ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">作废时间</dt>
                    <dd class="text-sm text-gray-800">{{ optional($processVoidOrder->voided_at)->format('Y-m-d H:i') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">纸质单据</dt>
                    <dd>
                        <button type="button" onclick="togglePaper({{ $processVoidOrder->id }})" id="paper-toggle-btn" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $processVoidOrder->paper_submitted ? 'bg-emerald-500' : 'bg-red-500' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 {{ $processVoidOrder->paper_submitted ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <span class="ml-2 text-xs" id="paper-toggle-label" style="color: {{ $processVoidOrder->paper_submitted ? '#059669' : '#dc2626' }}">{{ $processVoidOrder->paperSubmittedLabel() }}</span>
                    </dd>
                </div>
            </dl>
        </div>
        @endif

        {{-- 系统信息 --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">系统信息</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3">
                <div>
                    <dt class="text-xs text-gray-500">创建时间</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">最后更新</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">原始文件</dt>
                    <dd class="text-sm text-gray-800">{{ $processVoidOrder->source_file_name ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @push('scripts')
    <script>
        async function togglePaper(id) {
            const base = (window.APP_URL || '').replace(/\/+$/, '');
            const res = await fetch(base + '/process-void-orders/' + id + '/paper-toggle', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });
            if (!res.ok) return;
            const data = await res.json();
            const btn = document.getElementById('paper-toggle-btn');
            const label = document.getElementById('paper-toggle-label');
            if (!btn || !label) return;
            const dot = btn.querySelector('span');
            if (data.paper_submitted) {
                btn.classList.remove('bg-red-500');
                btn.classList.add('bg-emerald-500');
                dot.classList.remove('translate-x-1');
                dot.classList.add('translate-x-6');
                label.textContent = '已提交';
                label.style.color = '#059669';
            } else {
                btn.classList.remove('bg-emerald-500');
                btn.classList.add('bg-red-500');
                dot.classList.remove('translate-x-6');
                dot.classList.add('translate-x-1');
                label.textContent = '未提交';
                label.style.color = '#dc2626';
            }
        }
    </script>
    @endpush
</x-app-layout>
