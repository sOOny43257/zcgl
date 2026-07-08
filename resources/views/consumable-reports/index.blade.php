<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">耗材报表</h2>
                <p class="text-sm text-gray-500 mt-0.5">月度使用情况汇总</p>
            </div>
            <a href="{{ route('consumable-reports.export', ['month' => $yearMonth, 'type' => $reportType]) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 shadow-sm">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                导出 CSV
            </a>
        </div>
    </x-slot>

    <!-- Tabs & Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">月份</label>
                <input type="month" name="month" value="{{ $yearMonth }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">报表类型</label>
                <select name="type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="department" {{ $reportType == 'department' ? 'selected' : '' }}>部门消耗统计</option>
                    <option value="ranking" {{ $reportType == 'ranking' ? 'selected' : '' }}>耗材消耗排行</option>
                    <option value="turnover" {{ $reportType == 'turnover' ? 'selected' : '' }}>进销存报表</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700">查询</button>
        </form>
    </div>

    <!-- Report Content -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($reportType === 'department')
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">排名</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">部门</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">领用总量</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">领用次数</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $i => $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-center font-medium {{ $i < 3 ? 'text-blue-600' : 'text-gray-600' }}">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['department_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $row['total_qty'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $row['usage_count'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">该月暂无领用数据</td></tr>
                @endforelse
            </tbody>
        </table>

        @elseif($reportType === 'ranking')
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">排名</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">耗材名称</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">规格型号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">单位</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">消耗总量</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">领用次数</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">当前库存</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $i => $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-center font-medium {{ $i < 3 ? 'text-blue-600' : 'text-gray-600' }}">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['consumable_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $row['spec'] ?: '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $row['unit_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">{{ $row['total_qty'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $row['usage_count'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $row['current_stock'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">该月暂无消耗数据</td></tr>
                @endforelse
            </tbody>
        </table>

        @elseif($reportType === 'turnover')
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">耗材名称</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">规格型号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">单位</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">期初库存</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">本期入库</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">本期出库</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">期末库存</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['consumable_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $row['spec'] ?: '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $row['unit_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ $row['opening_stock'] }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-600">{{ $row['intake_qty'] > 0 ? '+' . $row['intake_qty'] : '0' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-blue-600">{{ $row['usage_qty'] > 0 ? '-' . $row['usage_qty'] : '0' }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">{{ $row['closing_stock'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">暂无耗材数据</td></tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>
</x-app-layout>
