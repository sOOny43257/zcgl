<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">新建权限单</h2>
                <p class="text-sm text-gray-500 mt-0.5">上传 Word 模板后系统会自动提取关键字段，可手动修改后再保存或提交</p>
            </div>
            <a href="{{ route('permission-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    @include('permission-orders._form')
</x-app-layout>
