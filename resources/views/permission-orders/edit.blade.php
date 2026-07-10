<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">编辑权限单</h2>
                <p class="text-sm text-gray-500 mt-0.5">草稿状态下可重新上传或修改提取信息，提交后将生成单号并进入已作废状态</p>
            </div>
            <a href="{{ route('permission-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回列表</a>
        </div>
    </x-slot>

    @include('permission-orders._form')
</x-app-layout>
