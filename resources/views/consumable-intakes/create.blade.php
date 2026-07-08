<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">新建入库单</h2>
    </x-slot>

    <div class="max-w-4xl">
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('consumable-intakes.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4" id="intakeForm">
            @csrf
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">入库日期 *</label>
                    <input type="date" name="intake_date" value="{{ old('intake_date', date('Y-m-d')) }}" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">供应商</label>
                    <select name="supplier_code" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">无</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->code }}" {{ old('supplier_code') == $s->code ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-700">入库明细</h3>
                    <button type="button" onclick="addItem()" class="text-blue-600 hover:text-blue-800 text-sm">+ 添加耗材</button>
                </div>
                <div id="items-container" class="space-y-2">
                    <div class="item-row grid grid-cols-12 gap-2 items-end bg-gray-50 rounded-xl p-3">
                        <div class="col-span-4">
                            <label class="block text-xs text-gray-500 mb-1">耗材 *</label>
                            <select name="items[0][consumable_id]" required class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm consumable-select">
                                <option value="">选择耗材</option>
                                @foreach($consumables as $c)
                                    <option value="{{ $c->id }}" data-unit="{{ $c->unitName() }}" data-stock="{{ $c->current_stock }}">{{ $c->name }} ({{ $c->spec ?: '无规格' }}) - 库存:{{ $c->current_stock }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">数量 *</label>
                            <input type="number" name="items[0][quantity]" min="1" required class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm" placeholder="0">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">单价</label>
                            <input type="number" name="items[0][unit_price]" min="0" step="0.01" class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm" placeholder="0.00">
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-500 mb-1">备注</label>
                            <input type="text" name="items[0][remarks]" class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        </div>
                        <div class="col-span-1">
                            <button type="button" onclick="this.closest('.item-row').remove()" class="p-2 text-red-400 hover:text-red-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">保存入库单</button>
                <a href="{{ route('consumable-intakes.index') }}" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200">取消</a>
            </div>
        </form>
    </div>

    <script>
    let itemIndex = 1;
    function addItem() {
        const container = document.getElementById('items-container');
        const firstRow = container.querySelector('.item-row');
        const clone = firstRow.cloneNode(true);
        // Update input names
        clone.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[0\]/, '[' + itemIndex + ']');
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
        });
        container.appendChild(clone);
        itemIndex++;
    }
    </script>
</x-app-layout>
