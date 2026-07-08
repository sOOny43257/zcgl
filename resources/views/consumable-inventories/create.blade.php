<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">新建盘点单</h2>
    </x-slot>

    <div class="max-w-4xl">
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('consumable-inventories.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">盘点日期 *</label>
                    <input type="date" name="inventory_date" value="{{ old('inventory_date', date('Y-m-d')) }}" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">盘点明细</h3>
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">耗材名称</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">规格</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">账面库存</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">实际数量 *</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">差异原因</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($consumables as $i => $c)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm text-gray-800">
                                    {{ $c->name }}
                                    <input type="hidden" name="items[{{ $i }}][consumable_id]" value="{{ $c->id }}">
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600">{{ $c->spec ?: '-' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-medium text-gray-800 book-qty" data-stock="{{ $c->current_stock }}">{{ $c->current_stock }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[{{ $i }}][actual_quantity]" min="0" value="{{ $c->current_stock }}" required class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm text-right actual-input" onchange="calcDiff(this)">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="items[{{ $i }}][reason]" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-sm reason-input" placeholder="差异时填写">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex space-x-3 pt-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">保存盘点单</button>
                <a href="{{ route('consumable-inventories.index') }}" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">取消</a>
            </div>
        </form>
    </div>

    <script>
    function calcDiff(input) {
        const row = input.closest('tr');
        const book = parseInt(row.querySelector('.book-qty').dataset.stock);
        const actual = parseInt(input.value);
        const reasonInput = row.querySelector('.reason-input');
        if (actual !== book) {
            reasonInput.classList.add('border-amber-300', 'bg-amber-50');
            reasonInput.setAttribute('required', 'required');
            reasonInput.placeholder = '请填写差异原因（必填）';
        } else {
            reasonInput.classList.remove('border-amber-300', 'bg-amber-50');
            reasonInput.removeAttribute('required');
            reasonInput.placeholder = '差异时填写';
        }
    }
    </script>
</x-app-layout>
