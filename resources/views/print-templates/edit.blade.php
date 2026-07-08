<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">编辑打印模板</h2>
                <p class="text-sm text-gray-500 mt-0.5">模板：{{ $printTemplate->name }}</p>
            </div>
            <a href="{{ route('print-templates.index') }}" class="px-3 py-2 border border-gray-200 text-gray-600 text-sm rounded-xl hover:bg-gray-50">返回模板列表</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="templateEditor()" x-init="init()">
        <form method="POST" action="{{ route('print-templates.update', $printTemplate) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 space-y-5">
                <h3 class="text-base font-semibold text-gray-800">基础设置</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">模板名称</label>
                        <input type="text" name="name" value="{{ old('name', $printTemplate->name) }}" required class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">页面方向</label>
                        <select name="orientation" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach(\App\Models\PrintTemplate::ORIENTATIONS as $orientation)
                            <option value="{{ $orientation }}" {{ old('orientation', $printTemplate->orientation) === $orientation ? 'selected' : '' }}>
                                {{ $orientation === 'landscape' ? '横向' : '纵向' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $printTemplate->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                            启用模板
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 space-y-5">
                <h3 class="text-base font-semibold text-gray-800">页眉信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">标题</label>
                        <input type="text" name="config[page][title]" value="{{ old('config.page.title', data_get($printTemplate->config, 'page.title')) }}" required class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">单号前缀</label>
                        <input type="text" name="config[page][order_no_prefix]" value="{{ old('config.page.order_no_prefix', data_get($printTemplate->config, 'page.order_no_prefix')) }}" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">展示字段</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($pageMetaOptions as $option)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="config[page][meta][]" value="{{ $option }}" {{ in_array($option, old('config.page.meta', data_get($printTemplate->config, 'page.meta', []))) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                            {{ $option }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">明细表格</h3>
                    <div class="flex items-center gap-4 text-sm">
                        <label class="flex items-center gap-2 text-gray-700">
                            <input type="checkbox" name="config[table][show_index]" value="1" {{ old('config.table.show_index', data_get($printTemplate->config, 'table.show_index', true)) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                            显示序号
                        </label>
                        <label class="flex items-center gap-2 text-gray-700">
                            <input type="checkbox" name="config[table][show_total]" value="1" {{ old('config.table.show_total', data_get($printTemplate->config, 'table.show_total', true)) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                            显示合计
                        </label>
                    </div>
                </div>
                <div class="space-y-3">
                    <template x-for="(column, index) in columns" :key="index">
                        <div class="flex items-center gap-3">
                            <select :name="'config[table][columns]['+index+'][key]'" x-model="column.key" class="flex-1 border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500">
                                @foreach($columnOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" :name="'config[table][columns]['+index+'][label]'" x-model="column.label" class="w-40 border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500" placeholder="表头名称">
                            <button type="button" @click="removeColumn(index)" class="text-red-400 hover:text-red-600 text-sm">移除</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addColumn()" class="px-4 py-2 border border-dashed border-gray-300 rounded-xl text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600">+ 添加列</button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6 space-y-5">
                <h3 class="text-base font-semibold text-gray-800">签名区</h3>
                <div class="space-y-3">
                    <template x-for="(sign, index) in signatures" :key="index">
                        <div class="flex items-center gap-3">
                            <input type="text" :name="'config[signatures]['+index+']'" x-model="signatures[index]" class="flex-1 border border-gray-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500" placeholder="签字人角色">
                            <button type="button" @click="removeSignature(index)" class="text-red-400 hover:text-red-600 text-sm">移除</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addSignature()" class="px-4 py-2 border border-dashed border-gray-300 rounded-xl text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600">+ 添加签名位</button>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('print-templates.edit', $printTemplate) }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">取消</a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">保存模板</button>
            </div>
        </form>

        <form method="POST" action="{{ route('print-templates.reset', $printTemplate) }}" onsubmit="return confirm('确定恢复为默认配置？当前修改将丢失。')">
            @csrf
            <button type="submit" class="px-4 py-2.5 text-red-600 border border-red-200 rounded-xl text-sm hover:bg-red-50">恢复默认配置</button>
        </form>
    </div>
</x-app-layout>

@once
@push('scripts')
<script>
function templateEditor() {
    return {
        columns: [],
        signatures: [],
        init() {
            try {
                this.columns = @json(data_get($printTemplate->config, 'table.columns', []));
                this.signatures = @json(data_get($printTemplate->config, 'signatures', []));
            } catch (e) {
                this.columns = [];
                this.signatures = [];
            }
        },
        addColumn() {
            this.columns.push({ key: 'name', label: '资产名称' });
        },
        removeColumn(index) {
            this.columns.splice(index, 1);
        },
        addSignature() {
            this.signatures.push('签字人');
        },
        removeSignature(index) {
            this.signatures.splice(index, 1);
        }
    };
}
</script>
@endpush
@endonce
