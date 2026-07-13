<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">CSV 批量导入/更新资产</h2>
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
            <div class="flex items-center gap-4 flex-wrap">
                <input type="file" accept=".csv,.txt" @change="uploadFile($event)" class="border border-gray-200 rounded-xl px-3 py-2 text-sm flex-1">
                <a href="{{ route('assets.template') }}" class="text-sm text-blue-600 hover:text-blue-800 whitespace-nowrap">下载模板</a>
            </div>
            <div class="mt-3 flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="updateMode" @change="onModeChange()"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">检测并更新已有数据（以"自有编号"为主键匹配）</span>
                </label>
                <span x-show="updateMode" class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">
                    仅财务编码变更不会生成调拨单
                </span>
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
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-xl inline-flex items-center justify-center text-xs mr-1">2</span>
                预览数据
                <span class="ml-1 text-sm font-normal text-gray-500">
                    共 <strong class="text-blue-600" x-text="rows.length"></strong> 条
                    <template x-if="updateMode">
                        <span>
                            · 新增 <strong class="text-emerald-600" x-text="newCount"></strong> 条
                            · 更新 <strong class="text-amber-600" x-text="updateCount"></strong> 条
                            · 无变化 <strong class="text-gray-400" x-text="noChangeCount"></strong> 条
                        </span>
                    </template>
                    · 合法 <strong class="text-emerald-600" x-text="validCount"></strong> 条
                    · 问题 <strong class="text-red-500" x-text="rows.length - validCount"></strong> 条
                </span>
            </h3>

            <!-- 变更摘要条（仅更新模式且有变更时显示） -->
            <div x-show="updateMode && updateCount > 0" class="mb-4 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                <div class="flex items-center gap-2 text-amber-800 font-medium text-sm mb-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    本次共有 <strong class="text-amber-900" x-text="updateCount"></strong> 条资产将被修改
                </div>
                <!-- 只显示有变更的行（如果变化很少，不显示所有行） -->
                <template x-for="(row, idx) in rows.filter(r => r._changeType === 'update')" :key="'sum-'+idx">
                    <div class="text-xs text-amber-700 mb-1 pb-1 border-b border-amber-100 last:border-0">
                        <span class="font-medium" x-text="row.asset_code || '(空)'"></span>
                        —
                        <span x-text="row.name || '未命名'"></span>
                        <span class="text-amber-500 mx-1">→</span>
                        <template x-for="(chg, fld) in row._changes" :key="fld">
                            <span class="inline-block bg-white px-1.5 py-0.5 rounded mr-1 mb-0.5 border border-amber-200">
                                <span class="font-medium" x-text="fieldLabel(fld)"></span>:
                                <span class="line-through text-red-400" x-text="chg.old || '(空)'"></span>
                                <span class="text-green-600 font-medium" x-text="chg.new || '(空)'"></span>
                            </span>
                        </template>
                    </div>
                </template>
            </div>

            <div class="overflow-x-auto max-h-[500px] overflow-y-auto border border-gray-100 rounded-2xl">
                <table class="min-w-full divide-y divide-gray-100 text-xs">
                    <thead class="bg-gray-50/50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-center text-gray-500 w-8"></th>
                            <th x-show="updateMode" class="px-2 py-2 text-center text-gray-500 w-8">类型</th>
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
                            <tr :class="{
                                'hover:bg-gray-50/50': row._valid && row._changeType !== 'update',
                                'bg-amber-50': row._changeType === 'update',
                                'bg-emerald-50': row._changeType === 'new' && updateMode,
                                'bg-red-50': !row._valid,
                                'opacity-50': row._changeType === 'no_change'
                            }">
                                <!-- 状态 -->
                                <td class="px-2 py-1 text-center w-8">
                                    <span x-show="row._valid && row._changeType !== 'no_change'" class="text-emerald-500 font-bold text-base">✓</span>
                                    <span x-show="!row._valid" class="text-red-500 font-bold text-base">✗</span>
                                    <span x-show="row._changeType === 'no_change'" class="text-gray-300 font-bold text-base">—</span>
                                </td>
                                <!-- 变更类型徽标 -->
                                <td x-show="updateMode" class="px-2 py-1 text-center w-8">
                                    <span x-show="row._changeType === 'new'"
                                          class="inline-block bg-emerald-100 text-emerald-700 text-[10px] px-1.5 py-0.5 rounded font-bold">新增</span>
                                    <span x-show="row._changeType === 'update'"
                                          class="inline-block bg-amber-100 text-amber-700 text-[10px] px-1.5 py-0.5 rounded font-bold">修改</span>
                                    <span x-show="row._changeType === 'no_change'"
                                          class="inline-block bg-gray-100 text-gray-400 text-[10px] px-1.5 py-0.5 rounded">不变</span>
                                </td>
                                <template x-for="col in columns" :key="col">
                                    <td class="px-0.5 py-1"
                                        :class="{
                                            'bg-amber-50 border-2 border-amber-400 rounded': row._fieldErrors && row._fieldErrors[col],
                                            'bg-yellow-100': row._changes && row._changes[col]
                                        }">
                                        <div class="relative">
                                            <span x-show="!row._editing || editingCol !== col"
                                                  @click="startEdit(idx, col)"
                                                  class="cursor-pointer block px-1 py-0.5 rounded min-w-[40px] text-xs font-medium"
                                                  :class="{
                                                      'text-red-700': row._fieldErrors && row._fieldErrors[col],
                                                      'font-mono': col === 'ip' || col === 'mac',
                                                      'text-amber-800': row._changes && row._changes[col]
                                                  }">
                                                <!-- 变更标记 + 旧值 -->
                                                <template x-if="row._changes && row._changes[col]">
                                                    <span>
                                                        <span class="line-through text-red-300 text-[10px] mr-1" x-text="row._changes[col].old || '∅'"></span>
                                                        <span class="font-bold" x-text="(row._display && row._display[col]) || row[col] || '-'"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!row._changes || !row._changes[col]">
                                                    <span x-text="(row._display && row._display[col]) || row[col] || '-'"></span>
                                                </template>
                                            </span>
                                            <input x-show="row._editing && editingCol === col"
                                                   x-model="row[col]"
                                                   @blur="stopEdit(idx)"
                                                   @keydown.enter="stopEdit(idx)"
                                                   class="w-full border border-blue-400 rounded px-1 py-0.5 text-xs focus:ring-2 focus:ring-blue-300"
                                                   :class="col === 'ip' || col === 'mac' ? 'font-mono' : ''">
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

            <p class="text-xs text-gray-400 mt-2">
                点击单元格即可编辑。
                <template x-if="updateMode"><span class="text-amber-600">黄色行=已有数据被修改，绿色行=新增，灰色行=无变化。</span></template>
                红色行为存在数据问题，请修正后再提交。
            </p>
        </div>

        <!-- 步骤3: 提交（固定底部栏） -->
        <div x-show="rows.length > 0" x-cloak
             class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl px-6 py-4 flex items-center justify-between"
             style="margin-left:232px;">
            <div>
                <span class="text-sm text-gray-700">
                    共 <strong class="text-blue-600" x-text="rows.length"></strong> 条
                    <template x-if="updateMode">
                        <span>
                            · 新增 <strong class="text-emerald-600" x-text="newCount"></strong>
                            · 更新 <strong class="text-amber-600" x-text="updateCount"></strong>
                        </span>
                    </template>
                    · 合法 <strong class="text-emerald-600" x-text="validCount"></strong> 条
                    · 问题 <strong class="text-red-500" x-text="rows.length - validCount"></strong> 条
                </span>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="result" x-cloak class="text-sm space-y-2 max-w-md">
                    <div :class="result.success ? 'text-emerald-600' : 'text-red-600'" x-text="result.message"></div>
                    <!-- 变更结果详情 -->
                    <div x-show="result && result.changed_details && result.changed_details.length > 0" class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 max-h-60 overflow-y-auto">
                        <div class="text-xs font-medium text-emerald-700 mb-1">变更明细：</div>
                        <template x-for="d in result.changed_details" :key="d.asset_code">
                            <div class="text-xs text-emerald-600 mb-1 pb-1 border-b border-emerald-100 last:border-0">
                                <span class="font-medium" x-text="d.asset_code"></span>
                                <span x-text="d.asset_name"></span>
                                <span class="text-emerald-400" x-text="d.type === 'insert' ? ' [新增]' : ' [修改]'"></span>
                                <template x-if="d.type === 'update' && d.changes">
                                    <span class="block ml-2 text-amber-600">
                                        <template x-for="(chg, fld) in d.changes" :key="fld">
                                            <span class="mr-2"><span x-text="fieldLabel(fld)"></span>: <span class="line-through text-red-300" x-text="chg.old || '∅'"></span> → <span class="font-medium" x-text="chg.new || '∅'"></span></span>
                                        </template>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div x-show="result.errors && result.errors.length > 0" class="bg-red-50 border border-red-200 rounded-xl p-3 max-h-40 overflow-y-auto">
                        <div class="text-xs font-medium text-red-700 mb-1">错误明细：</div>
                        <template x-for="e in result.errors" :key="e">
                            <div class="text-xs text-red-600" x-text="e"></div>
                        </template>
                    </div>
                </div>
                <button @click="submitImport()" :disabled="submitting || validCount === 0"
                        class="px-8 py-3 bg-blue-600 text-white text-base font-bold rounded-xl hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg transition-all">
                    <span x-show="!submitting">
                        <template x-if="!updateMode">提交导入（<span x-text="validCount"></span> 条）</template>
                        <template x-if="updateMode">确认执行（<span x-text="validCount"></span> 条）</template>
                    </span>
                    <span x-show="submitting">正在处理...</span>
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
        updateMode: false,
        newCount: 0,
        updateCount: 0,
        noChangeCount: 0,

        get validCount() {
            return this.rows.filter(r => r._valid && r._changeType !== 'no_change').length;
        },

        fieldLabel(f) {
            const labels = {
                asset_code:'编号', financial_code:'财务编码', name:'名称', department:'部门',
                room:'房间', ip:'IP', mac:'MAC', sn:'SN', brand:'品牌', model:'型号',
                category:'类别', status:'状态', user:'使用人', remarks:'备注'
            };
            return labels[f] || f;
        },

        onModeChange() {
            // 如果已经上传了文件，重新解析
            if (this.rows.length > 0) {
                this.rows = [];
                this.result = null;
            }
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
                let url;
                if (this.updateMode) {
                    url = '{{ route('assets.parseBatchUpdate') }}';
                } else {
                    url = '{{ route('assets.parseCsv') }}';
                }
                const res = await fetch(url, {
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
                    if (this.updateMode) {
                        this.newCount = data.newCount || 0;
                        this.updateCount = data.updateCount || 0;
                        this.noChangeCount = data.noChangeCount || 0;
                    }
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

            if (!this.updateMode) {
                // 纯导入模式：验证
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
            }
        },

        async submitImport() {
            const validRows = this.rows.filter(r => r._valid && r._changeType !== 'no_change');
            if (validRows.length === 0) return;
            this.submitting = true;

            const payload = validRows.map(r => {
                const d = {};
                this.columns.forEach(c => d[c] = r[c] || '');
                return d;
            });

            try {
                let url;
                if (this.updateMode) {
                    url = '{{ route('assets.submitBatchUpdate') }}';
                } else {
                    url = '{{ route('assets.batchImport') }}';
                }
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ rows: payload, file_name: '手动上传' })
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
