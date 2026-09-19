<x-layouts.app title="Dashboard">
    <x-page-header title="Station Dashboard" subtitle="Real-time operations, daily finances, and smart reorder forecasting">
        <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>New Order</span>
        </a>
    </x-page-header>

    <!-- Top 4 Metric Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <x-stat-card
            title="Today's Orders"
            value="{{ $todayOrdersCount }}"
            subtext="{{ $todayDeliveredCount }} delivered today"
            iconBg="bg-blue-50 text-blue-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Today's Revenue"
            value="₱{{ number_format($todayRevenue, 2) }}"
            subtext="Water sales recorded today"
            iconBg="bg-emerald-50 text-emerald-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Pending Deliveries"
            value="{{ $pendingDeliveriesCount }}"
            subtext="Confirmed or out on route"
            iconBg="bg-indigo-50 text-indigo-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Low Stock Items"
            value="{{ $lowStockCount }}"
            subtext="Items at or below reorder threshold"
            iconBg="{{ $lowStockCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-500' }}"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Charts Section: Side by Side on Desktop >= 1024px, Stacking on Tablet/Mobile -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- 7-Day Income vs Expense Bar Chart -->
        <x-card title="Income vs Expense" subtitle="Past 7 days financial flow (Philippine Peso ₱)">
            <div class="h-64 sm:h-72 w-full">
                <canvas id="incomeExpenseChart"></canvas>
            </div>
        </x-card>

        <!-- 7-Day Daily Orders Line Chart -->
        <x-card title="Daily Orders Trend" subtitle="Past 7 days refill volume and request count">
            <div class="h-64 sm:h-72 w-full">
                <canvas id="dailyOrdersChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- Smart Reorder Alerts & Action Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Smart Reorder Alerts (2 Cols) -->
        <div class="lg:col-span-2">
            <x-card title="Smart Reorder Alerts" subtitle="Customers predicted due for water refill based on historical frequency">
                <x-slot:action>
                    <a href="{{ route('customers.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All Customers &rarr;</a>
                </x-slot:action>

                @if ($dueCustomers->isEmpty())
                    <x-empty-state message="No customers due for refill right now." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($dueCustomers as $customer)
                            <div class="py-3.5 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0">
                                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-800 truncate">{{ $customer->name }}</h4>
                                        <p class="text-xs text-slate-500 truncate">{{ $customer->phone }} • {{ $customer->area ?? 'General' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <x-status-badge :status="$customer->cycle_status" />
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Ordered <strong>{{ $customer->days_since_last_order }}d</strong> ago (Cycle: {{ $customer->avg_reorder_days }}d)
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Action Items / Alerts (1 Col) -->
        <div>
            <x-card title="Station Alerts & Attention" subtitle="Inventory reorders and credit follow-ups">
                @if ($topLowStock->isEmpty() && $overdueCreditCustomers->isEmpty())
                    <div class="py-12 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700">All clear — no alerts.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @if ($topLowStock->isNotEmpty())
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500 mb-2">Low Inventory Warnings</p>
                                <div class="space-y-2">
                                    @foreach ($topLowStock as $item)
                                        <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-100 flex items-center justify-between">
                                            <div>
                                                <p class="text-xs font-bold text-rose-900">{{ $item->name }}</p>
                                                <p class="text-[10px] text-rose-600 mt-0.5">Threshold: {{ $item->reorder_threshold }} {{ $item->unit }}</p>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-200 text-rose-800">
                                                {{ $item->quantity }} {{ $item->unit }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($overdueCreditCustomers->isNotEmpty())
                            <div class="pt-2 border-t border-slate-100">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 mb-2">Overdue Utang / Credit</p>
                                <div class="space-y-2">
                                    @foreach ($overdueCreditCustomers as $credit)
                                        <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 flex items-center justify-between">
                                            <div>
                                                <p class="text-xs font-bold text-amber-900">{{ $credit->customer_name }}</p>
                                                <p class="text-[10px] text-amber-700 mt-0.5">Status: {{ $credit->status }}</p>
                                            </div>
                                            <span class="text-xs font-bold text-amber-900">
                                                ₱{{ number_format($credit->amount_owed, 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Chart.js Setup Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Income vs Expense 7-Day Chart
            const ctx1 = document.getElementById('incomeExpenseChart');
            if (ctx1 && window.Chart) {
                new window.Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($chartLabels) !!},
                        datasets: [
                            {
                                label: 'Income',
                                data: {!! json_encode($chartIncome) !!},
                                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                                borderRadius: 8,
                                borderSkipped: false,
                            },
                            {
                                label: 'Expense',
                                data: {!! json_encode($chartExpense) !!},
                                backgroundColor: 'rgba(244, 63, 94, 0.75)',
                                borderRadius: 8,
                                borderSkipped: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { boxWidth: 12, font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': ₱' + Number(context.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) { return '₱' + value; },
                                    font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                                },
                                grid: { color: 'rgba(226, 232, 240, 0.6)' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 } }
                            }
                        }
                    }
                });
            }

            // 2. Daily Orders 7-Day Line Chart (Integer-only Y-axis)
            const ctx2 = document.getElementById('dailyOrdersChart');
            if (ctx2 && window.Chart) {
                new window.Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($chartLabels) !!},
                        datasets: [{
                            label: 'Orders',
                            data: {!! json_encode($chartOrdersCount) !!},
                            borderColor: '#0284c7',
                            backgroundColor: 'rgba(2, 132, 199, 0.12)',
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#0284c7',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    precision: 0,
                                    font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                                },
                                grid: { color: 'rgba(226, 232, 240, 0.6)' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 } }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.app>
