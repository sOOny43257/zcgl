<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">编辑入库单 <span class="text-sm text-gray-400 font-mono">（草稿）</span></h2>
            <a href="{{ route('intakes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('intakes.update', $intake) }}" id="intakeForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="_action" value="save" id="formAction">

        @php $items = $intake->draft_data['items'] ?? []; @endphp

        <!-- 单据信息 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">单据信息</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">入库日期 <span class="text-red-500">*</span></label>
                    <input type="date" name="intake_date" value="{{ old('intake_date', $intake->intake_date?->format('Y-m-d')) }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">供应商</label>
                    <input type="text" name="supplier" value="{{ old('supplier', $intake->supplier) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">采购单号/合同号</label>
                    <input type="text" name="purchase_order_no" value="{{ old('purchase_order_no', $intake->purchase_order_no) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">总金额</label>
                    <input type="number" step="0.01" name="total_amount" value="{{ old('total_amount', $intake->total_amount) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">验收人</label>
                    <input type="text" name="approver" value="{{ old('approver', $intake->approver) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks', $intake->remarks) }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- 资产明细 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="intakeItems()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">资产明细</h3>
                <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 text-sm rounded-lg hover:bg-blue-100">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    添加一行
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-2 py-2 text-left font-medium w-8">#</th>
                            <th class="px-2 py-2 text-left font-medium">资产名称 <span class="text-red-500">*</span></th>
                            <th class="px-2 py-2 text-left font-medium">类别</th>
                            <th class="px-2 py-2 text-left font-medium">品牌</th>
                            <th class="px-2 py-2 text-left font-medium">规格型号</th>
                            <th class="px-2 py-2 text-left font-medium">SN序列号</th>
                            <th class="px-2 py-2 text-left font-medium">部门</th>
                            <th class="px-2 py-2 text-left font-medium">房间号</th>
                            <th class="px-2 py-2 text-left font-medium">使用人</th>
                            <th class="px-2 py-2 text-left font-medium">单价</th>
                            <th class="px-2 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-t border-gray-100">
                                <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][name]'" x-model="item.name" required class="w-full border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][category]'" x-model="item.category" class="w-28 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][brand]'" x-model="item.brand" class="w-20 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][model]'" x-model="item.model" class="w-28 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][sn]'" x-model="item.sn" class="w-28 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][department]'" x-model="item.department" class="w-24 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][room]'" x-model="item.room" class="w-16 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="text" :name="'items['+index+'][user]'" x-model="item.user" class="w-20 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" :name="'items['+index+'][purchase_price]'" x-model="item.purchase_price" class="w-24 border border-gray-200 rounded py-1.5 px-2 text-sm"></td>
                                <td class="px-2 py-2">
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 p-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-between">
            <form method="POST" action="{{ route('intakes.update', $intake) }}" onsubmit="return confirm('确定删除此草稿？')">
                @csrf @method('PUT')
                <input type="hidden" name="_action" value="delete">
                <button type="submit" class="px-4 py-2.5 text-red-600 border border-red-300 rounded-lg text-sm hover:bg-red-50">删除草稿</button>
            </form>
            <div class="space-x-3">
                <a href="{{ route('intakes.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
                <button type="button" @click="submitDraft()" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
                <button type="button" @click="submitFinal()" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">提交入库</button>
            </div>
        </div>
    </form>

    <script>
    function intakeItems() {
        return {
            items: @json($items),
            addItem() {
                this.items.push({ name: '', category: '', brand: '', model: '', sn: '', department: '', room: '', user: '', purchase_price: '', financial_code: '', remarks: '' });
            },
            removeItem(index) {
                if (this.items.length > 1) this.items.splice(index, 1);
            }
        };
    }
    function submitDraft() {
        document.getElementById('formAction').value = 'save';
        document.getElementById('intakeForm').submit();
    }
    function submitFinal() {
        document.getElementById('formAction').value = 'submit';
        document.getElementById('intakeForm').submit();
    }
    </script>
</x-app-layout>
