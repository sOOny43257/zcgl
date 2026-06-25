<x-app-layout>
    @push('head')
    <script src="{{ asset('vendor/print.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/print.min.css') }}">
    <style>
        .cat-checkbox:checked + label { background: #3b82f6; color: white; border-color: #3b82f6; }
        .cat-checkbox + label { cursor: pointer; transition: all 0.15s; }
    </style>
    @endpush

    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">资产盘点确认表</h1>
    </x-slot>

    <div x-data="checkTable()" class="space-y-4">
        <!-- 类别筛选 -->
        <div class="glass rounded-2xl p-4">
            <p class="text-sm text-gray-500 mb-3">选择盘点类别（可多选，默认全部）</p>
            <div class="flex flex-wrap gap-2" id="categoryCheckboxes">
                @foreach($categories as $cat)
                <div class="flex items-center">
                    <input type="checkbox" id="cat_{{ $cat->code }}" value="{{ $cat->code }}"
                           class="cat-checkbox hidden"
                           @change="toggleCategory('{{ $cat->code }}')">
                    <label for="cat_{{ $cat->code }}"
                           class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white text-sm text-gray-700 hover:border-blue-400">
                        {{ $cat->name }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <!-- 部门列表表格 -->
        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-3 text-center w-10">
                                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll">
                            </th>
                            <th class="px-3 py-3 text-center w-12">序号</th>
                            <th class="px-4 py-3 text-left">部门名称</th>
                            <template x-for="cat in activeCategories" :key="cat.code">
                                <th class="px-3 py-3 text-center" x-text="cat.name"></th>
                            </template>
                            <th class="px-3 py-3 text-center w-24">预览打印</th>
                            <th class="px-3 py-3 text-center w-24">直接打印</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departments as $index => $deptCode)
                        @php $deptName = $deptMap[$deptCode] ?? $deptCode; @endphp
                        <tr class="border-t border-gray-200 hover:bg-gray-50" data-dept="{{ $deptCode }}">
                            <td class="px-3 py-2.5 text-center">
                                <input type="checkbox" class="dept-checkbox" value="{{ $deptCode }}" x-model="checkedDepts">
                            </td>
                            <td class="px-3 py-2.5 text-center text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-2.5 font-medium">{{ $deptName }}</td>
                            @foreach($categories as $cat)
                            <td class="px-3 py-2.5 text-center cat-count cat-{{ $cat->code }}"
                                data-count="{{ $counts[$deptCode][$cat->code] ?? 0 }}">
                                {{ $counts[$deptCode][$cat->code] ?? 0 }}
                            </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center">
                                <button @click="previewPrint('{{ $deptCode }}')"
                                        class="px-3 py-1.5 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                    预览打印
                                </button>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <button @click="directPrint('{{ $deptCode }}')"
                                        class="px-3 py-1.5 text-xs bg-green-500 text-white rounded-lg hover:bg-green-600">
                                    直接打印
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div x-show="checkedDepts.length === 0 && departments.length === 0" class="text-center py-12 text-gray-400">
                暂无可盘点的资产数据
            </div>
        </div>

        <!-- 底部操作栏 -->
        <div class="glass rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll" id="selectAllBottom">
                <label for="selectAllBottom" class="text-sm text-gray-600 cursor-pointer">全选</label>
                <span class="text-sm text-gray-400" x-text="'已选 ' + checkedDepts.length + ' 个部门'"></span>
            </div>
            <button @click="batchPrint"
                    :disabled="checkedDepts.length === 0"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                打印选中部门盘点表
            </button>
        </div>

        <!-- 预览弹窗（直接渲染到 body 避免被侧边栏遮挡） -->
        <div x-show="showPreview" x-cloak
             style="position: fixed; left: 0; top: 0; width: 100vw; height: 100vh; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem;"
             @click.self="showPreview = false">
            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"></div>
            <div style="position: relative; background: white; border-radius: 16px; box-shadow: 0 25px 80px rgba(0,0,0,0.3); width: 100%; max-width: 1200px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;">
                    <h3 style="font-size: 18px; font-weight: 600;">打印预览</h3>
                    <div style="display: flex; gap: 8px;">
                        <button @click="printIframe()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 10px; font-size: 14px; cursor: pointer;">打印</button>
                        <button @click="showPreview = false" style="padding: 8px; background: none; border: none; color: #6b7280; font-size: 20px; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                </div>
                <div style="flex: 1; background: #e5e7eb; position: relative; min-height: 0;">
                    <div x-show="previewLoading" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8); z-index: 10;">
                        <span style="color: #9ca3af;">加载中...</span>
                    </div>
                    <iframe :srcdoc="previewHtml" id="previewFrame"
                            style="width: 100%; height: 100%; border: none; background: white; min-height: 70vh;"></iframe>
                </div>
            </div>
        </div>

        <!-- 打印内容容器（隐藏） -->
        <div id="printContainer" style="display:none;"></div>
    </div>

    <script>
        function checkTable() {
            return {
                checkedDepts: [],
                selectAll: false,
                showPreview: false,
                previewLoading: false,
                previewHtml: '',
                allDeptCodes: @json($departments),
                allCategories: @json($categories),
                activeCategories: @json($categories),

                toggleCategory(code) {
                    const checks = document.querySelectorAll('.cat-checkbox:checked');
                    const selectedCodes = Array.from(checks).map(c => c.value);
                    this.activeCategories = selectedCodes.length === 0
                        ? this.allCategories
                        : this.allCategories.filter(c => selectedCodes.includes(c.code));
                    this.updateColumns();
                },

                updateColumns() {
                    // 显示/隐藏类别列
                    document.querySelectorAll('.cat-count').forEach(td => {
                        td.style.display = 'none';
                    });
                    this.activeCategories.forEach(cat => {
                        document.querySelectorAll('.cat-' + cat.code).forEach(td => {
                            td.style.display = '';
                        });
                    });
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        this.checkedDepts = [...this.allDeptCodes];
                    } else {
                        this.checkedDepts = [];
                    }
                },

                getSelectedCategories() {
                    const checks = document.querySelectorAll('.cat-checkbox:checked');
                    if (checks.length === 0) return [];
                    return Array.from(checks).map(c => c.value);
                },

                buildPrintUrl(deptCode) {
                    let url = '{{ route('assets.checkPrint') }}?departments[]=' + encodeURIComponent(deptCode);
                    const cats = this.getSelectedCategories();
                    cats.forEach(c => {
                        url += '&categories[]=' + encodeURIComponent(c);
                    });
                    return url;
                },

                buildBatchPrintUrl() {
                    if (this.checkedDepts.length === 0) return '';
                    let url = '{{ route('assets.checkPrint') }}?';
                    this.checkedDepts.forEach(d => {
                        url += 'departments[]=' + encodeURIComponent(d) + '&';
                    });
                    const cats = this.getSelectedCategories();
                    cats.forEach(c => {
                        url += 'categories[]=' + encodeURIComponent(c) + '&';
                    });
                    return url;
                },

                async previewPrint(deptCode) {
                    this.previewLoading = true; this.previewHtml = '';
                    const url = this.buildPrintUrl(deptCode);
                    try {
                        const resp = await fetch(url);
                        this.previewHtml = await resp.text();
                        this.showPreview = true;
                    } catch (e) {
                        alert('加载打印内容失败：' + e.message);
                    }
                    this.previewLoading = false;
                },

                printIframe() {
                    const iframe = document.getElementById('previewFrame');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    }
                },

                async directPrint(deptCode) {
                    const url = this.buildPrintUrl(deptCode);
                    try {
                        const resp = await fetch(url);
                        const html = await resp.text();
                        document.getElementById('printContainer').innerHTML = html;
                        printJS({
                            printable: 'printContainer',
                            type: 'html',
                            targetStyles: ['*'],
                            style: `
                                @page { size: A4 landscape; margin: 8mm; }
                                thead { display: table-header-group; }
                                tr { page-break-inside: avoid; }
                                .dept-section { page-break-before: always; }
                                .dept-section:first-child { page-break-before: auto; }
                            `,
                        });
                    } catch (e) {
                        alert('加载打印内容失败：' + e.message);
                    }
                },

                async batchPrint() {
                    if (this.checkedDepts.length === 0) return;
                    const url = this.buildBatchPrintUrl();
                    try {
                        const resp = await fetch(url);
                        const html = await resp.text();
                        document.getElementById('printContainer').innerHTML = html;
                        printJS({
                            printable: 'printContainer',
                            type: 'html',
                            targetStyles: ['*'],
                            style: `
                                @page { size: A4 landscape; margin: 8mm; }
                                thead { display: table-header-group; }
                                tr { page-break-inside: avoid; }
                                .dept-section { page-break-before: always; }
                                .dept-section:first-child { page-break-before: auto; }
                            `,
                        });
                    } catch (e) {
                        alert('加载打印内容失败：' + e.message);
                    }
                },

                init() {
                    this.updateColumns();
                    // 初始化全选
                    this.$watch('checkedDepts', val => {
                        this.selectAll = val.length === this.allDeptCodes.length && val.length > 0;
                    });
                }
            };
        }
    </script>
</x-app-layout>
