<x-layouts.app title="Finance & Bookkeeping">
    <x-page-header title="Finance & Bookkeeping" subtitle="Station revenue logs, utility expenses, operational costs, and net margins">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'add-transaction-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Record Transaction</span>
        </button>
    </x-page-header>

    <!-- All-Time Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <x-stat-card
            title="Total Income (All-Time)"
            value="₱{{ number_format($totalIncome, 2) }}"
            subtext="Gross station revenue & water sales"
            iconBg="bg-emerald-50 text-emerald-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Total Expenses (All-Time)"
            value="₱{{ number_format($totalExpenses, 2) }}"
            subtext="Utilities, supplies, and maintenance"
            iconBg="bg-rose-50 text-rose-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Net Profit (All-Time)"
            value="₱{{ number_format($netProfit, 2) }}"
            subtext="{{ $netProfit >= 0 ? 'Profitable cumulative station balance' : 'Negative operating balance' }}"
            iconBg="{{ $netProfit >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600' }}"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Charts Section: 6 Months Bar Chart & Expense Donut Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- 6-Month Income vs Expense Bar Chart (2 cols) -->
        <div class="lg:col-span-2">
            <x-card title="Income vs Expense (Past 6 Months)" subtitle="Monthly aggregate comparison in Philippine Peso (₱)">
                <div class="h-64 sm:h-72 w-full">
                    <canvas id="sixMonthFinanceChart"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Expense Breakdown Donut Chart (1 col) -->
        <div>
            <x-card title="Expense Breakdown" subtitle="Distribution by category">
                <div class="h-64 sm:h-72 w-full flex items-center justify-center">
                    <canvas id="expenseDonutChart"></canvas>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Transactions Log Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-1.5">
                <a
                    href="{{ route('finance.index', ['type' => '', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ empty($typeFilter) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    All Transactions
                </a>
                <a
                    href="{{ route('finance.index', ['type' => 'income', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $typeFilter === 'income' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    Income Only
                </a>
                <a
                    href="{{ route('finance.index', ['type' => 'expense', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $typeFilter === 'expense' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    Expenses Only
                </a>
            </div>

            <form method="GET" action="{{ route('finance.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="type" value="{{ $typeFilter }}">
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search category or note..."
                        class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($transactions->isEmpty())
            <x-empty-state message="No transactions yet." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Date</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Description</th>
                            <th class="py-3.5 px-4">Payment Method</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($transactions as $tx)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 sm:px-6 font-medium text-slate-600 whitespace-nowrap">
                                    {{ $tx->date->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <x-status-badge :status="ucfirst($tx->type)" />
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    {{ $tx->category }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-sm truncate" title="{{ $tx->description }}">
                                    {{ $tx->description ?: '-' }}
                                    @if ($tx->order_id)
                                        <span class="text-[10px] text-blue-600 ml-1 font-semibold">(Order #{{ $tx->order_id }})</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ $tx->payment_method }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right font-bold whitespace-nowrap {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $tx->type === 'income' ? '+' : '-' }}₱{{ number_format($tx->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Record Transaction Modal -->
    <x-modal name="add-transaction-modal" title="Record Manual Transaction" maxWidth="max-w-md">
        <form method="POST" action="{{ route('finance.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                        <option value="expense" selected>Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Date *</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Category *</label>
                <select name="category" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                    <option value="Electricity">Electricity (Power Bill)</option>
                    <option value="Water Bill">Water Bill (Water Utility)</option>
                    <option value="Rider Wages">Rider Wages & Payroll</option>
                    <option value="Supplies">Station Supplies (Caps, Seals, Jugs)</option>
                    <option value="Maintenance">Maintenance & Repairs</option>
                    <option value="Water Sales">Water Sales (Manual)</option>
                    <option value="Other">Other Expense / Income</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Amount (₱) *</label>
                    <input type="number" step="0.01" name="amount" min="0.01" required placeholder="0.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                        <option value="Cash" selected>Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Maya">Maya</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Description</label>
                <textarea name="description" rows="2" placeholder="Invoice details, payee, or notes..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-transaction-modal')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Transaction</button>
            </div>
        </form>
    </x-modal>

    <!-- Chart.js Setup -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. 6-Month Income vs Expense
            const ctx1 = document.getElementById('sixMonthFinanceChart');
            if (ctx1 && window.Chart) {
                new window.Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($sixMonthLabels) !!},
                        datasets: [
                            {
                                label: 'Income',
                                data: {!! json_encode($sixMonthIncome) !!},
                                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                                borderRadius: 8,
                            },
                            {
                                label: 'Expense',
                                data: {!! json_encode($sixMonthExpenses) !!},
                                backgroundColor: 'rgba(244, 63, 94, 0.75)',
                                borderRadius: 8,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12 } },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.dataset.label + ': ₱' + Number(ctx.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: function (v) { return '₱' + v; } }
                            }
                        }
                    }
                });
            }

            // 2. Expense Breakdown Donut Chart
            const ctx2 = document.getElementById('expenseDonutChart');
            if (ctx2 && window.Chart) {
                const rawCategories = {!! json_encode($expenseCategories) !!};
                const labels = Object.keys(rawCategories);
                const values = Object.values(rawCategories);

                new window.Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: labels.length ? labels : ['No Expenses'],
                        datasets: [{
                            data: values.length ? values : [1],
                            backgroundColor: [
                                '#f43f5e', '#fbbf24', '#0284c7', '#10b981', '#8b5cf6', '#64748b'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.label + ': ₱' + Number(ctx.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }
        });
    </script>
</x-layouts.app>
