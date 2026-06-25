<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">新建入库单</h2>
            <a href="{{ route('intakes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('intakes.store') }}" id="intakeForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_action" value="draft" id="formAction">

        <!-- 单据信息 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="{ get totalAmount() { return parseFloat(document.getElementById('total_amount')?.value) || 0 }, get itemsSum() { return 0 } }" x-init="$watch('$el', ()=>{})">
            <h3 class="text-base font-semibold text-gray-800 mb-4">单据信息</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">入库日期 <span class="text-red-500">*</span></label>
                    <input type="date" name="intake_date" value="{{ old('intake_date', date('Y-m-d')) }}" required class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('intake_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">供应商</label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">采购单号/合同号</label>
                    <input type="text" name="purchase_order_no" value="{{ old('purchase_order_no') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">总金额</label>
                    <input type="number" step="0.01" name="total_amount" id="total_amount" value="{{ old('total_amount') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" oninput="window._intakeAmountCheck && window._intakeAmountCheck()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">验收人</label>
                    <input type="text" name="approver" value="{{ old('approver') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">入库说明</label>
                    <textarea name="description" rows="2" placeholder="请输入入库说明（选填）" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 资产明细 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4" x-data="intakeItems()" x-init="init()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">资产明细</h3>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 mr-3">
                        <label class="text-sm text-gray-500">批量添加：</label>
                        <input type="number" x-model="batchCount" min="1" max="999" value="1"
                               class="w-20 border border-gray-300 rounded-lg py-1.5 px-2 text-sm text-center focus:ring-2 focus:ring-blue-500">
                        <button type="button" @click="addBatchItems()" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 text-sm rounded-lg hover:bg-green-100">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            批量添加
                        </button>
                    </div>
                    <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 text-sm rounded-lg hover:bg-blue-100">
                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        添加一行
                    </button>
                </div>
            </div>

            @error('items')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror

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
                            <th class="px-2 py-2 w-20"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-t border-gray-100">
                                <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][name]'" x-model="item.name" required class="w-full border border-gray-200 rounded py-1.5 px-2 text-sm" placeholder="资产名称">
                                </td>
                                <td class="px-2 py-2">
                                    <select :name="'items['+index+'][category]'" x-model="item.category" class="w-36 border border-gray-200 rounded py-1.5 px-2 text-sm bg-white">
                                        <option value="">请选择</option>
                                        <template x-for="cat in categories" :key="cat.code">
                                            <option :value="cat.code" x-text="cat.name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][brand]'" x-model="item.brand" class="w-20 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][model]'" x-model="item.model" class="w-28 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][sn]'" x-model="item.sn" class="w-28 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <select :name="'items['+index+'][department]'" x-model="item.department" class="w-32 border border-gray-200 rounded py-1.5 px-2 text-sm bg-white">
                                        <option value="">请选择</option>
                                        <template x-for="dept in departments" :key="dept.code">
                                            <option :value="dept.code" x-text="dept.name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][room]'" x-model="item.room" class="w-16 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" :name="'items['+index+'][user]'" x-model="item.user" class="w-20 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" step="0.01" :name="'items['+index+'][purchase_price]'" x-model="item.purchase_price" @input="calcSum()" class="w-24 border border-gray-200 rounded py-1.5 px-2 text-sm">
                                </td>
                                <td class="px-2 py-2">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="copyItem(index)" class="text-blue-400 hover:text-blue-600 p-1" title="复制此行">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 p-1" title="删除此行">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="items.length === 0" class="text-center text-gray-400 py-6">请点击"添加一行"开始录入资产明细</p>

            <!-- 金额汇总 -->
            <div x-show="items.length > 0" class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                <div class="flex items-center gap-4">
                    <span class="text-gray-500">共 <strong class="text-gray-800" x-text="items.length"></strong> 项资产</span>
                    <span class="text-gray-500">明细合计：<strong class="text-blue-600" x-text="itemsSum.toFixed(2)"></strong> 元</span>
                    <span x-show="totalAmount > 0" class="text-gray-500">总金额：<strong x-text="totalAmount.toFixed(2)"></strong> 元</span>
                    <span x-show="totalAmount > 0 && Math.abs(itemsSum - totalAmount) > 0.01" class="text-red-500 font-medium">
                        <svg class="h-4 w-4 inline mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        金额不一致！差额 <span x-text="Math.abs(itemsSum - totalAmount).toFixed(2)"></span> 元
                    </span>
                    <span x-show="totalAmount > 0 && Math.abs(itemsSum - totalAmount) <= 0.01" class="text-green-600 font-medium">
                        <svg class="h-4 w-4 inline mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        金额一致
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="if(confirm('确定清空所有明细？')) { items.splice(1); calcSum(); }" x-show="items.length > 1" class="text-red-400 hover:text-red-600 text-xs">清空全部</button>
                </div>
            </div>
        </div>

        <!-- 附件上传 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-4">附件</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">上传佐证材料（支持图片、PDF、Office文档，单个文件最大10MB）</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
                       class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('intakes.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">取消</a>
            <button type="button" onclick="submitDraft()" class="px-6 py-2.5 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50">保存草稿</button>
            <button type="button" onclick="submitFinal()" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">提交入库</button>
        </div>
    </form>

    <script>
    function newItem() {
        return { name: '', category: '', brand: '', model: '', sn: '', department: '', room: '', user: '', purchase_price: '', financial_code: '', remarks: '' };
    }

    function intakeItems() {
        return {
            items: [newItem()],
            categories: [],
            departments: [],
            batchCount: 10,
            itemsSum: 0,
            totalAmount: 0,

            async init() {
                try {
                    const [catRes, deptRes] = await Promise.all([
                        fetch(APP_URL + '/api/codes?type=category'),
                        fetch(APP_URL + '/api/depts')
                    ]);
                    if (catRes.ok) this.categories = await catRes.json();
                    if (deptRes.ok) this.departments = await deptRes.json();
                } catch(e) {}

                this.calcSum();
                const self = this;
                window._intakeAmountCheck = function() { self.calcSum(); };
            },

            calcSum() {
                this.itemsSum = this.items.reduce((s, it) => s + (parseFloat(it.purchase_price) || 0), 0);
                this.totalAmount = parseFloat(document.getElementById('total_amount')?.value) || 0;
            },

            addItem() {
                this.items.push(newItem());
            },

            addBatchItems() {
                const count = Math.min(Math.max(parseInt(this.batchCount) || 1, 1), 999);
                const tpl = this.items.length > 0 ? { ...this.items[this.items.length - 1] } : newItem();
                for (let i = 0; i < count; i++) {
                    const copy = { ...tpl, sn: '' };
                    this.items.push(copy);
                }
                this.calcSum();
            },

            copyItem(index) {
                const src = this.items[index];
                const copy = { ...src };
                this.items.splice(index + 1, 0, copy);
                this.calcSum();
            },

            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                    this.calcSum();
                }
            }
        };
    }

    function submitDraft() {
        document.getElementById('formAction').value = 'draft';
        document.getElementById('intakeForm').submit();
    }

    function submitFinal() {
        // 校验总金额与明细合计
        const totalEl = document.getElementById('total_amount');
        const totalVal = parseFloat(totalEl?.value) || 0;
        if (totalVal > 0) {
            const inputs = document.querySelectorAll('input[name$="[purchase_price]"]');
            let sum = 0;
            inputs.forEach(inp => { sum += parseFloat(inp.value) || 0; });
            if (Math.abs(sum - totalVal) > 0.01) {
                alert('总金额（' + totalVal.toFixed(2) + '）与明细单价合计（' + sum.toFixed(2) + '）不一致，无法提交！\n\n请修正后再提交。');
                return;
            }
        }
        document.getElementById('formAction').value = 'submit';
        document.getElementById('intakeForm').submit();
    }
    </script>
</x-app-layout>
