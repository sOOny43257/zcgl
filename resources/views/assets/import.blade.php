<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">CSV 批量导入资产</h2>
            <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← 返回资产列表</a>
        </div>
    </x-slot>

    <div x-data="importer()" class="max-w-full">
        <!-- 步骤1: 上传 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-xl inline-flex items-center justify-center text-xs mr-2">1</span>
                上传 CSV 文件
            </h3>
            <div class="flex items-center gap-4">
                <input type="file" accept=".csv,.txt" @change="uploadFile($event)" class="border border-gray-200 rounded-xl px-3 py-2 text-sm flex-1">
                <a href="{{ route('assets.template') }}" class="text-sm text-blue-600 hover:text-blue-800 whitespace-nowrap">下载模板</a>
            </div>
            <div x-show="uploading" class="mt-3 text-sm text-blue-600">正在解析...</div>
            <div x-show="parseError" class="mt-3 text-sm text-red-600" x-text="parseError"></div>
            <div class="mt-3 bg-blue-50 rounded-2xl p-4 text-xs text-blue-700">
                <p class="font-medium mb-1">CSV 列名（中英文均可）：</p>
                <p class="font-mono text-xs leading-relaxed">自有编号, 财务编码, 资产名称, 部门, 房间号, IP地址, MAC地址, SN序列号, 品牌, 规格型号, 类别, 状态, 使用人, 备注</p>
                <p class="mt-1 text-gray-400">（也支持英文: asset_code, financial_code, name, department, room, ip, mac, sn, brand, model, category, status, user, remarks）</p>
            </div>
        </div>

        <!-- 步骤2: 预览编辑 -->
        <div x-show="rows.length > 0" class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 mb-6" x-cloak>
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-xl inline-flex items-center justify-center text-xs mr-2">2</span>
                预览与编辑数据
                <span class="ml-3 text-sm font-normal text-gray-500">
                    共 <strong class="text-blue-600" x-text="rows.length"></strong> 条，
                    <strong class="text-emerald-600" x-text="validCount"></strong> 条合法，
                    <strong class="text-red-500" x-text="rows.length - validCount"></strong> 条有问题
                </span>
            </h3>

            <div class="overflow-x-auto max-h-[500px] overflow-y-auto border border-gray-100 rounded-2xl">
                <table class="min-w-full divide-y divide-gray-100 text-xs">
                    <thead class="bg-gray-50/50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-center text-gray-500 w-8"></th>
                            <th class="px-2 py-2 text-left text-gray-500">自有编号</th>
                            <th class="px-2 py-2 text-left text-gray-500">财务编码</th>
                            <th class="px-2 py-2 text-left text-gray-500">名称</th>
                            <th class="px-2 py-2 text-left text-gray-500">部门</th>
                            <th class="px-2 py-2 text-left text-gray-500">房间</th>
                            <th class="px-2 py-2 text-left text-gray-500 w-28">IP*</th>
                            <th class="px-2 py-2 text-left text-gray-500 w-28">MAC*</th>
                            <th class="px-2 py-2 text-left text-gray-500">SN</th>
                            <th class="px-2 py-2 text-left text-gray-500">品牌</th>
                            <th class="px-2 py-2 text-left text-gray-500">型号</th>
                            <th class="px-2 py-2 text-left text-gray-500">类别</th>
                            <th class="px-2 py-2 text-left text-gray-500">状态</th>
                            <th class="px-2 py-2 text-left text-gray-500">使用人</th>
                            <th class="px-2 py-2 text-left text-gray-500">备注</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr :class="row._valid ? 'hover:bg-gray-50/50' : 'bg-red-50 border-l-4 border-red-500'">
                                <!-- 状态标识 -->
                                <td class="px-2 py-1 text-center w-8">
                                    <span x-show="row._valid" class="text-emerald-500 font-bold text-base">✅</span>
                                    <span x-show="!row._valid" class="text-red-500 font-bold text-base">❌</span>
                                </td>
                                <template x-for="col in columns" :key="col">
                                    <td class="px-0.5 py-1" :class="(row._fieldErrors && row._fieldErrors[col]) ? 'bg-amber-50 border-2 border-amber-400 rounded' : ''">
                                        <div class="relative">
                                            <span x-show="!row._editing || editingCol !== col"
                                                  @click="startEdit(idx, col)"
                                                  class="cursor-pointer block px-1 py-0.5 rounded min-w-[40px] text-xs font-medium"
                                                  :class="(row._fieldErrors && row._fieldErrors[col]) ? 'text-red-700' : (col === 'ip' || col === 'mac' ? 'font-mono' : '')"
                                                  x-text="(row._display && row._display[col]) || row[col] || '-'"></span>
                                            <input x-show="row._editing && editingCol === col"
                                                   x-model="row[col]"
                                                   @blur="stopEdit(idx)"
                                                   @keydown.enter="stopEdit(idx)"
                                                   class="w-full border border-blue-400 rounded px-1 py-0.5 text-xs focus:ring-2 focus:ring-blue-300"
                                                   :class="col === 'ip' || col === 'mac' ? 'font-mono' : ''">
                                            <!-- 字段错误提示 -->
                                            <div x-show="row._fieldErrors && row._fieldErrors[col]"
                                                 class="absolute -top-8 left-0 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded whitespace-nowrap z-10 shadow-lg"
                                                 x-text="row._fieldErrors[col]"></div>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-400 mt-2">点击单元格即可编辑。红色行为存在数据问题，请修正后再提交。</p>
        </div>

        <!-- 步骤3: 提交（固定底部栏） -->
        <div x-show="rows.length > 0" x-cloak
             class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl px-6 py-4 flex items-center justify-between"
             style="margin-left:232px;">
            <div>
                <span class="text-sm text-gray-700">共 <strong class="text-blue-600" x-text="rows.length"></strong> 条，
                    合法 <strong class="text-emerald-600" x-text="validCount"></strong> 条，
                    问题 <strong class="text-red-500" x-text="rows.length - validCount"></strong> 条</span>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="result" x-cloak class="text-sm space-y-2">
                    <div :class="result.success ? 'text-emerald-600' : 'text-red-600'" x-text="result.message"></div>
                    <div x-show="result.errors && result.errors.length > 0" class="bg-red-50 border border-red-200 rounded-xl p-3 max-h-40 overflow-y-auto">
                        <div class="text-xs font-medium text-red-700 mb-1">跳过明细：</div>
                        <template x-for="e in result.errors" :key="e">
                            <div class="text-xs text-red-600" x-text="e"></div>
                        </template>
                    </div>
                </div>
                <button @click="submitImport()" :disabled="submitting || validCount === 0"
                        class="px-8 py-3 bg-blue-600 text-white text-base font-bold rounded-xl hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg transition-all">
                    <span x-show="!submitting">提交导入（<span x-text="validCount"></span> 条）</span>
                    <span x-show="submitting">正在导入...</span>
                </button>
            </div>
        </div>
    </div>
@push('scripts')
<script>
function importer() {
    return {
        rows: [],
        columns: ['asset_code','financial_code','name','department','room','ip','mac','sn','brand','model','category','status','user','remarks'],
        editingCol: null,
        uploading: false,
        submitting: false,
        parseError: '',
        result: null,

        get validCount() {
            return this.rows.filter(r => r._valid).length;
        },

        async uploadFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.uploading = true;
            this.parseError = '';
            this.result = null;

            const form = new FormData();
            form.append('csv_file', file);

            try {
                const res = await fetch('{{ route('assets.parseCsv') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: form
                });
                if (!res.ok) {
                    const err = await res.json();
                    this.parseError = err.error || '解析失败';
                    this.rows = [];
                } else {
                    const data = await res.json();
                    this.rows = data.rows.map(r => ({ ...r, _editing: false }));
                }
            } catch(e) {
                this.parseError = '网络错误';
            }
            this.uploading = false;
        },

        startEdit(idx, col) {
            this.rows[idx]._editing = true;
            this.editingCol = col;
        },

        stopEdit(idx) {
            const row = this.rows[idx];
            const col = this.editingCol;
            row._editing = false;
            this.editingCol = null;

            // 重新验证所有字段
            const errs = [];
            const fieldErrs = {};
            if (row.ip && !/^(\d{1,3}\.){3}\d{1,3}$/.test(row.ip) && !row.ip.includes(':')) {
                errs.push('IP格式不合法'); fieldErrs['ip'] = 'IP格式不合法';
            }
            if (row.mac && !/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/.test(row.mac)) {
                errs.push('MAC格式不合法'); fieldErrs['mac'] = 'MAC格式不合法';
            }
            row._errors = errs;
            row._fieldErrors = fieldErrs;
            row._valid = errs.length === 0;
        },

        async submitImport() {
            const validRows = this.rows.filter(r => r._valid);
            if (validRows.length === 0) return;
            this.submitting = true;

            const payload = validRows.map(r => {
                const d = {};
                this.columns.forEach(c => d[c] = r[c] || '');
                return d;
            });

            try {
                const res = await fetch('{{ route('assets.batchImport') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ rows: payload })
                });
                this.result = await res.json();
            } catch(e) {
                this.result = { success: false, message: '提交失败' };
            }
            this.submitting = false;
        }
    };
}
</script>
@endpush
</x-app-layout>
