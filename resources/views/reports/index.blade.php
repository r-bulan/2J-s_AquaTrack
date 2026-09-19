<x-layouts.app title="Financial Reports">
    <x-page-header title="Financial Reports" subtitle="BIR-style summary statements, rolling sales ledgers, and exportable logs">
        <div class="flex items-center gap-2 flex-wrap">
            <a
                href="{{ route('reports.pdf', ['period' => $period]) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-md shadow-blue-500/20 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export BIR PDF</span>
            </a>

            <a
                href="{{ route('reports.sales-csv', ['period' => $period]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Sales CSV</span>
            </a>

            <a
                href="{{ route('reports.expense-csv', ['period' => $period]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Expense CSV</span>
            </a>
        </div>
    </x-page-header>

    <!-- Rolling Period Selector -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Report Period:</span>
                <a
                    href="{{ route('reports.index', ['period' => 'week']) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'week' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    This Week (7 Days)
                </a>
                <a
                    href="{{ route('reports.index', ['period' => 'month']) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    This Month (30 Days)
                </a>
                <a
                    href="{{ route('reports.index', ['period' => 'year']) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'year' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    This Year (365 Days)
                </a>
            </div>

            <div class="text-xs text-slate-500 font-medium">
                Scope: <strong>{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <x-stat-card
            title="Total Period Revenue"
            value="₱{{ number_format($totalIncome, 2) }}"
            subtext="{{ count($salesLogs) }} sales transactions"
            iconBg="bg-emerald-50 text-emerald-600"
        />

        <x-stat-card
            title="Total Period Expenses"
            value="₱{{ number_format($totalExpense, 2) }}"
            subtext="{{ count($expenseLogs) }} expense entries"
            iconBg="bg-rose-50 text-rose-600"
        />

        <x-stat-card
            title="Period Net Profit"
            value="₱{{ number_format($netProfit, 2) }}"
            subtext="{{ $periodLabel }}"
            iconBg="{{ $netProfit >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600' }}"
        />
    </div>

    <!-- Side by Side Tables: Sales Log & Expense Log -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Log Table -->
        <x-card title="Sales Log" subtitle="Water refill sales records for selected period">
            @if ($salesLogs->isEmpty())
                <x-empty-state message="No sales records in this period." />
            @else
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase text-[10px]">
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Category</th>
                                <th class="py-2.5 px-3">Description</th>
                                <th class="py-2.5 px-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($salesLogs as $s)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">{{ $s->date->format('M d, Y') }}</td>
                                    <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $s->category }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 truncate max-w-xs" title="{{ $s->description }}">{{ $s->description }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-600 whitespace-nowrap">₱{{ number_format($s->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <!-- Expense Log Table -->
        <x-card title="Expense Log" subtitle="Operating and maintenance expenditures for selected period">
            @if ($expenseLogs->isEmpty())
                <x-empty-state message="No expenses recorded in this period." />
            @else
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase text-[10px]">
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Category</th>
                                <th class="py-2.5 px-3">Description</th>
                                <th class="py-2.5 px-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($expenseLogs as $e)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">{{ $e->date->format('M d, Y') }}</td>
                                    <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $e->category }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 truncate max-w-xs" title="{{ $e->description }}">{{ $e->description }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-rose-600 whitespace-nowrap">₱{{ number_format($e->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
