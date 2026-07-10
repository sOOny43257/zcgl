<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">权限单详情</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $permissionOrder->order_no ?: '草稿' }} — {{ $permissionOrder->statusLabel() }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($permissionOrder->source_doc_path)
                <a href="{{ route('permission-orders.download', $permissionOrder) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    下载原件
                </a>
                @endif
                @if($permissionOrder->isDraft())
                <a href="{{ route('permission-orders.edit', $permissionOrder) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">编辑</a>
                @endif
                <a href="{{ route('permission-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
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
                    <dd class="text-sm font-mono font-medium text-blue-700">{{ $permissionOrder->order_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">状态</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $permissionOrder->isDraft() ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600' }}">
                            {{ $permissionOrder->statusLabel() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">填报部门</dt>
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->department ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">填写日期</dt>
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->fill_date ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- 权限调整明细 --}}
        @php $items = $permissionOrder->items ?? []; @endphp
        @if(count($items) > 0)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">权限调整明细</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">姓名</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">涉及业务系统</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">原岗位</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">增加岗位</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">减少岗位</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($items as $idx => $item)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-3 py-2.5 text-sm text-gray-500">{{ $idx + 1 }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-800">{{ $item['names'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-800">{{ $item['business_system'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-800">{{ $item['original_position'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-800 whitespace-pre-wrap">{{ $item['added_position'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-800 whitespace-pre-wrap">{{ $item['removed_position'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- 提交信息 --}}
        @if($permissionOrder->isVoided())
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">提交信息</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3">
                <div>
                    <dt class="text-xs text-gray-500">修改人</dt>
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->voided_by ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">修改时间</dt>
                    <dd class="text-sm text-gray-800">{{ optional($permissionOrder->voided_at)->format('Y-m-d H:i') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">纸质单据</dt>
                    <dd>
                        <button type="button" onclick="togglePaper({{ $permissionOrder->id }})" id="paper-toggle-btn" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $permissionOrder->paper_submitted ? 'bg-emerald-500' : 'bg-red-500' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 {{ $permissionOrder->paper_submitted ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <span class="ml-2 text-xs" id="paper-toggle-label" style="color: {{ $permissionOrder->paper_submitted ? '#059669' : '#dc2626' }}">{{ $permissionOrder->paperSubmittedLabel() }}</span>
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
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">最后更新</dt>
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">原始文件</dt>
                    <dd class="text-sm text-gray-800">{{ $permissionOrder->source_file_name ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @push('scripts')
    <script>
        async function togglePaper(id) {
            const base = (window.APP_URL || '').replace(/\/+$/, '');
            const res = await fetch(base + '/permission-orders/' + id + '/paper-toggle', {
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
