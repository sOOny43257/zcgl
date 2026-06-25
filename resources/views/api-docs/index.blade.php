<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">API 接口文档</h2>
                <p class="text-sm text-gray-500 mt-0.5">RESTful API — 供第三方系统集成调用</p>
            </div>
            <span class="text-xs text-gray-400">Base: <code class="bg-gray-100 px-2 py-0.5 rounded text-blue-600">{{ url('/api') }}</code></span>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- 认证说明 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <span class="w-8 h-8 bg-amber-100 rounded-xl flex items-center justify-center mr-3">
                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
                认证方式 — Bearer Token
            </h3>
            <p class="text-sm text-gray-600 mb-4">除 <code class="bg-gray-100 px-1 rounded">/api/stats</code> 和 <code class="bg-gray-100 px-1 rounded">/data-update/receive</code> 外，所有接口需要在请求头携带 API Token。</p>

            <div class="bg-gray-900 rounded-2xl p-4 font-mono text-sm text-gray-100 overflow-x-auto">
                Authorization: Bearer <span class="text-emerald-400">YOUR_API_TOKEN</span>
            </div>

            <!-- Token 管理 -->
            <div class="mt-4 p-4 bg-gray-50 rounded-2xl" x-data="tokenManager()">
                <h4 class="text-sm font-medium text-gray-700 mb-2">管理你的 API Token</h4>
                <div class="flex gap-2 mb-3">
                    <button @click="createToken()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">
                        生成新 Token
                    </button>
                </div>
                <div x-show="newToken" x-cloak class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-xs text-amber-700 mb-1 font-medium">新 Token（仅显示一次，请立即复制）</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-white px-3 py-2 rounded-lg text-xs font-mono text-gray-800 break-all" x-text="newToken"></code>
                        <button @click="copyToken()" class="shrink-0 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs hover:bg-gray-50">复制</button>
                    </div>
                </div>
                <template x-if="tokens.length > 0">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500">已有 Token：</p>
                        <template x-for="t in tokens" :key="t.id">
                            <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-gray-200">
                                <div>
                                    <span class="text-xs font-medium text-gray-700" x-text="t.name"></span>
                                    <span class="text-xs text-gray-400 ml-2" x-text="'创建于 ' + t.created_at"></span>
                                </div>
                                <form method="POST" action="/profile/tokens" class="inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="token_id" :value="t.id">
                                    <button class="text-xs text-red-500 hover:text-red-700">撤销</button>
                                </form>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- 接口列表 -->
        @php
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/stats', 'auth' => false, 'desc' => '系统统计概览', 'params' => [], 'example' => 'curl ' . url('/api/stats')],
            ['method' => 'GET', 'path' => '/api/assets', 'auth' => true, 'desc' => '资产列表（支持筛选/搜索/分页）', 'params' => [
                'search' => '搜索关键词（匹配编号/名称/IP）',
                'departments[]' => '部门筛选（可多选）',
                'statuses[]' => '状态筛选（可多选）',
                'categories[]' => '类别筛选（可多选）',
                'sort' => '排序字段，默认 id',
                'direction' => '排序方向 asc/desc，默认 desc',
                'per_page' => '每页条数，默认20，最大100',
                'page' => '页码',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/assets?search=C26&statuses[]=在用&per_page=10') . '"'],
            ['method' => 'GET', 'path' => '/api/assets/search', 'auth' => true, 'desc' => '快速搜索资产（返回最多30条）', 'params' => [
                'q' => '搜索关键词（编号/名称/IP）',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/assets/search?q=C26') . '"'],
            ['method' => 'GET', 'path' => '/api/assets/status/{status}', 'auth' => true, 'desc' => '按状态查询资产', 'params' => [
                'status' => '状态值：在用/闲置/维修/借用/待报废/报废',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/assets/status/借用') . '"'],
            ['method' => 'GET', 'path' => '/api/assets/{code}', 'auth' => true, 'desc' => '资产详情（按自有编号或ID）', 'params' => [
                'code' => '资产自有编号（如 C26001）或数据库 ID',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/assets/C26001') . '"'],
            ['method' => 'GET', 'path' => '/api/borrows', 'auth' => true, 'desc' => '借用记录列表', 'params' => [
                'status' => '筛选：active（借用中）/ returned（已归还），不传则全部',
                'per_page' => '每页条数，默认20，最大100',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/borrows?status=active') . '"'],
            ['method' => 'GET', 'path' => '/api/transfers', 'auth' => true, 'desc' => '调拨单列表', 'params' => [
                'cancelled' => '筛选：true（已作废）/ false（有效），不传则全部',
                'per_page' => '每页条数，默认20，最大100',
            ], 'example' => 'curl -H "Authorization: Bearer TOKEN" "' . url('/api/transfers?cancelled=false') . '"'],
            ['method' => 'POST', 'path' => '/data-update/receive', 'auth' => false, 'desc' => '接收客户端脚本提交的资产数据（无需Token）', 'params' => [
                'name' => '使用人姓名（可选）',
                'department' => '部门拼音简写，如 bgs（可选）',
                'room' => '房间号（可选）',
                'ip' => 'IP地址',
                'mac' => 'MAC地址',
                'sn' => 'SN序列号（资产唯一标识）',
            ], 'example' => 'curl -s -X POST ' . url('/data-update/receive') . ' -d "name=张三&department=bgs&room=301&ip=192.168.1.100&mac=AA:BB:CC:DD:EE:FF&sn=TEST001"'],
        ];
        @endphp

        @foreach($endpoints as $ep)
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-3 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold text-white
                        {{ $ep['method'] === 'GET' ? 'bg-emerald-500' : ($ep['method'] === 'POST' ? 'bg-blue-500' : 'bg-amber-500') }}">
                        {{ $ep['method'] }}
                    </span>
                    <code class="text-sm font-mono text-gray-800 bg-gray-100 px-2 py-0.5 rounded">{{ $ep['path'] }}</code>
                    @if($ep['auth'])
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700">
                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        需认证
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">公开</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mb-3">{{ $ep['desc'] }}</p>

                @if(!empty($ep['params']))
                <div class="mb-3">
                    <p class="text-xs text-gray-500 uppercase mb-2">参数</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($ep['params'] as $name => $desc)
                        <div class="flex items-start gap-2 text-sm">
                            <code class="shrink-0 bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono text-gray-700">{{ $name }}</code>
                            <span class="text-gray-500 text-xs">{{ $desc }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div x-data="{ open: false }">
                    <button @click="open = !open" class="text-xs text-blue-600 hover:text-blue-800 flex items-center">
                        <svg class="h-3.5 w-3.5 mr-1 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        curl 示例
                    </button>
                    <div x-show="open" x-cloak class="mt-2 bg-gray-900 rounded-xl p-3 font-mono text-xs text-gray-100 overflow-x-auto">
                        {{ $ep['example'] }}
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- 客户端脚本示例 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <span class="w-8 h-8 bg-gray-100 rounded-xl flex items-center justify-center mr-3">
                    <svg class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
                客户端 Shell 脚本提交示例
            </h3>
            <p class="text-sm text-gray-600 mb-3">在麒麟操作系统的终端中运行以下脚本，自动采集本机信息并提交到服务器：</p>
            <pre class="bg-gray-900 rounded-xl p-5 text-xs text-gray-100 overflow-x-auto leading-relaxed">#!/bin/bash
# 保存为 collect_and_submit.sh，执行 chmod +x collect_and_submit.sh

read -p "姓名（可选）: " name
read -p "科所拼音简写（可选，如 bgs）: " department
read -p "房间号（可选）: " room

IP=$(ip -4 addr show scope global | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n1)
MAC=$(ip link show | awk '/ether/ {print $2; exit}')
SN=$(sudo dmidecode -s system-serial-number 2>/dev/null || echo 'N/A')

echo "采集数据: name=$name dept=$department room=$room ip=$IP mac=$MAC sn=$SN"
echo "正在提交..."

curl -s -X POST {{ url('/data-update/receive') }} \
  -d "name=$name&department=$department&room=$room&ip=$IP&mac=$MAC&sn=$SN"

echo ""
echo "✅ 提交完成！管理员可在数据更新页面查看。"</pre>
            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-sm text-amber-800">
                <p class="font-medium mb-1">注意事项</p>
                <ul class="text-xs space-y-1 text-amber-700">
                    <li>• 部门字段请使用<strong>拼音简写</strong>（如 <code class="bg-amber-100 px-1 rounded">bgs</code> = 办公室），否则提交后在管理页面会被标红提示</li>
                    <li>• 部门编码列表可在网站「数据字典 → 部门编码」中查看</li>
                    <li>• 已存在的 SN 再次提交将更新该资产信息（部门变更会自动生成调拨单）</li>
                    <li>• 脚本中的 <code class="bg-amber-100 px-1 rounded">{{ url('/data-update/receive') }}</code> 部署到服务器后需改为实际地址</li>
                </ul>
            </div>
        </div>

        <!-- 响应格式说明 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100/50 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">响应格式</h3>
            <p class="text-sm text-gray-600 mb-3">所有接口返回 JSON，统一格式如下：</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-2">列表响应</p>
                    <pre class="bg-gray-900 rounded-xl p-4 text-xs text-gray-100 overflow-x-auto">{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 55
  }
}</pre>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-2">错误响应</p>
                    <pre class="bg-gray-900 rounded-xl p-4 text-xs text-gray-100 overflow-x-auto">{
  "success": false,
  "message": "资产不存在"
}</pre>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
function tokenManager() {
    return {
        tokens: [],
        newToken: null,
        async init() {
            try {
                const res = await fetch('/api/tokens', { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.tokens = await res.json();
            } catch(e) {}
        },
        async createToken() {
            try {
                const res = await fetch('/api/tokens', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: 'API Token ' + new Date().toLocaleDateString() })
                });
                if (res.ok) {
                    const data = await res.json();
                    this.newToken = data.token;
                    this.tokens.push({ id: data.id, name: data.name, created_at: new Date().toISOString().slice(0, 10) });
                }
            } catch(e) {}
        },
        async copyToken() {
            await navigator.clipboard.writeText(this.newToken);
            alert('Token 已复制到剪贴板');
        }
    };
}
</script>
@endpush
