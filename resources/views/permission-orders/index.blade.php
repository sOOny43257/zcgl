<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">权限单汇总</h2>
                <p class="text-sm text-gray-500 mt-0.5">上传 Word 模板提取岗位权限调整信息，支持保存草稿与提交作废流程</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('permission-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">新建权限单</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
        <div class="p-4 border-b bg-gray-50/30">
            <form method="GET" action="{{ route('permission-orders.index') }}" class="flex items-center gap-3 flex-wrap">
                <div class="flex-1 min-w-[220px] relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索单号、填报部门..." class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <div class="flex items-center gap-2">
                    <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">全部状态</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>草稿</option>
                        <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>已作废</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <span class="text-gray-400 text-sm">至</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="show_voided" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ request('show_voided') ? 'checked' : '' }}>
                    仅查看作废单据
                </label>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">搜索</button>
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to', 'show_voided']))
                <a href="{{ route('permission-orders.index') }}" class="px-4 py-2 border border-red-200 text-red-600 text-sm rounded-xl hover:bg-red-50">重置</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">单号</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">填报部门</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">填写日期</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">涉及人员</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">涉及业务系统</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">纸质单据</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">更新时间</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($orders as $order)
                    @php
                        $items = $order->items ?? [];
                        $allNames = collect($items)->pluck('names')->filter()->implode('；');
                        $allSystems = collect($items)->pluck('business_system')->filter()->unique()->implode('；');
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-3 py-2.5 text-sm font-mono font-medium">
                            <a href="{{ route('permission-orders.show', $order) }}" class="text-blue-700 hover:text-blue-900 hover:underline">{{ $order->order_no ?: '草稿' }}</a>
                        </td>
                        <td class="px-3 py-2.5 text-sm text-gray-700">{{ $order->department ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-700">{{ $order->fill_date ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ Str::limit($allNames, 30) ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm text-gray-600">{{ Str::limit($allSystems, 30) ?: '-' }}</td>
                        <td class="px-3 py-2.5 text-sm">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $order->isDraft() ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600' }}">
                                {{ $order->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-sm" id="paper-cell-{{ $order->id }}">
                            <button type="button" onclick="togglePaper({{ $order->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $order->paper_submitted ? 'bg-emerald-500' : 'bg-red-500' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 {{ $order->paper_submitted ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                            <span class="ml-2 text-xs {{ $order->paper_submitted ? 'text-emerald-600' : 'text-red-500' }}">{{ $order->paperSubmittedLabel() }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-sm text-gray-500 whitespace-nowrap">{{ $order->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5 text-sm space-x-2 whitespace-nowrap">
                            @if($order->source_doc_path)
                            <a href="{{ route('permission-orders.download', $order) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">下载原件</a>
                            @endif
                            @if($order->isDraft())
                            <a href="{{ route('permission-orders.edit', $order) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">编辑</a>
                            <form method="POST" action="{{ route('permission-orders.destroy', $order) }}" class="inline" onsubmit="return confirm('确认删除此草稿？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs font-medium">删除</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-gray-500">暂无权限单记录</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span>共 <strong>{{ $orders->total() }}</strong> 条记录</span>
            </div>
            {{ $orders->links() }}
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
            const cell = document.getElementById('paper-cell-' + id);
            if (!cell) return;
            const button = cell.querySelector('button');
            const span = cell.querySelector('span.ml-2');
            if (data.paper_submitted) {
                button.classList.remove('bg-red-500');
                button.classList.add('bg-emerald-500');
                button.querySelector('span').classList.remove('translate-x-1');
                button.querySelector('span').classList.add('translate-x-6');
                span.textContent = '已提交';
                span.classList.remove('text-red-500');
                span.classList.add('text-emerald-600');
            } else {
                button.classList.remove('bg-emerald-500');
                button.classList.add('bg-red-500');
                button.querySelector('span').classList.remove('translate-x-6');
                button.querySelector('span').classList.add('translate-x-1');
                span.textContent = '未提交';
                span.classList.remove('text-emerald-600');
                span.classList.add('text-red-500');
            }
        }
    </script>
    @endpush
</x-app-layout>
