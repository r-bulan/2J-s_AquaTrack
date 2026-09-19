<x-layouts.app title="Customers Management">
    <x-page-header title="Customer Directory" subtitle="Manage customer ledgers, jug deposits, refill cycles, and loyalty rewards">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'add-customer-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Add Customer</span>
        </button>
    </x-page-header>

    <!-- Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('customers.index') }}" class="flex items-center justify-between gap-4">
            <div class="relative w-full max-w-md">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by customer name, phone, area, or barangay..."
                    class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            @if ($search)
                <a href="{{ route('customers.index') }}" class="text-xs text-slate-500 hover:text-slate-800 p-2">Clear Filter</a>
            @endif
        </form>
    </div>

    <!-- Customer Cards Grid: 3 cols desktop >= 1024px, 2 cols tablet 768-1023px, 1 col mobile < 768px -->
    @if ($customers->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <x-empty-state message="No customers yet.">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'add-customer-modal')"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"
                >
                    Register First Customer
                </button>
            </x-empty-state>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" x-data="{ selectedCustomer: null, drawerOpen: false }">
            @foreach ($customers as $cust)
                @php
                    $isDue = $cust->isDueForRefill();
                    $amountOwed = $cust->creditLedger?->amount_owed ?? 0;
                    $jugsHeld = $cust->jugLedger?->jugs_held ?? 0;
                    $refills = $cust->loyaltyRecord?->refills_count ?? 0;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <!-- Header & Badges -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-500 to-cyan-400 text-white flex items-center justify-center font-extrabold text-sm shadow-sm shrink-0">
                                    {{ strtoupper(substr($cust->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">{{ $cust->name }}</h3>
                                    <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $cust->phone }}
                                    </p>
                                </div>
                            </div>
                            @if ($isDue)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0 animate-pulse">
                                    Due for Refill
                                </span>
                            @endif
                        </div>

                        <!-- Address -->
                        <div class="p-2.5 rounded-xl bg-slate-50 text-xs text-slate-600 mb-4">
                            <p class="truncate font-medium">{{ $cust->address }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $cust->barangay ? $cust->barangay . ' • ' : '' }}{{ $cust->area ?? 'General Area' }}</p>
                        </div>

                        <!-- 3 Metrics Columns -->
                        <div class="grid grid-cols-3 gap-2 text-center pt-2 border-t border-slate-100 mb-4">
                            <div class="p-2 rounded-xl bg-slate-50/75">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Orders</span>
                                <span class="text-xs font-bold text-slate-800">{{ $cust->orders->count() }}</span>
                            </div>
                            <div class="p-2 rounded-xl bg-blue-50/75">
                                <span class="text-[10px] uppercase font-bold text-blue-600 block">Jugs Held</span>
                                <span class="text-xs font-bold text-blue-900">{{ $jugsHeld }}</span>
                            </div>
                            <div class="p-2 rounded-xl {{ $amountOwed > 0 ? 'bg-rose-50/75 text-rose-800' : 'bg-slate-50/75 text-slate-800' }}">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Utang</span>
                                <span class="text-xs font-bold">{{ $amountOwed > 0 ? '₱' . number_format($amountOwed, 0) : '₱0' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <button
                        type="button"
                        x-on:click="
                            selectedCustomer = {{ Js::from($cust) }};
                            $dispatch('open-modal', 'customer-detail-{{ $cust->id }}');
                        "
                        class="w-full py-2 px-3 rounded-xl border border-slate-200 hover:border-blue-500 hover:text-blue-600 text-xs font-semibold text-slate-600 transition flex items-center justify-center gap-1.5"
                    >
                        <span>View Customer Profile</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Customer Detail Modal -->
                    <x-modal name="customer-detail-{{ $cust->id }}" title="{{ $cust->name }}" maxWidth="max-w-2xl">
                        <div class="space-y-5 text-xs">
                            <!-- Contact Information -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Contact & Location</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-slate-500">Phone:</p>
                                        <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $cust->phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Email:</p>
                                        <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $cust->email ?? 'None' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-slate-500">Delivery Address:</p>
                                        <p class="font-semibold text-slate-800 mt-0.5">{{ $cust->address }}</p>
                                        <p class="text-slate-400 text-[11px]">{{ $cust->barangay }} • {{ $cust->area }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3 Core Ledgers (Jug Ledger, Credit, Loyalty) -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Gallon / Jug Ledger -->
                                <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-100">
                                    <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block mb-1">Gallon / Jug Ledger</span>
                                    <div class="text-xl font-extrabold text-blue-900">{{ $jugsHeld }} <span class="text-xs font-normal">held</span></div>
                                    <div class="mt-2 text-[11px] text-blue-700">
                                        Deposit: <strong>₱{{ number_format($cust->jugLedger?->deposit_amount ?? 0, 2) }}</strong>
                                        ({{ $cust->jugLedger?->deposit_status ?? 'Unpaid' }})
                                    </div>
                                </div>

                                <!-- Credit Ledger -->
                                <div class="p-4 rounded-2xl {{ $amountOwed > 0 ? 'bg-rose-50/70 border-rose-100 text-rose-900' : 'bg-slate-50 border-slate-100 text-slate-800' }} border">
                                    <span class="text-[10px] font-bold uppercase tracking-wider block mb-1 {{ $amountOwed > 0 ? 'text-rose-700' : 'text-slate-500' }}">Credit Balance</span>
                                    <div class="text-xl font-extrabold">{{ $amountOwed > 0 ? '₱' . number_format($amountOwed, 2) : '₱0.00' }}</div>
                                    <div class="mt-2 text-[11px] {{ $amountOwed > 0 ? 'text-rose-700' : 'text-slate-500' }}">
                                        Status: <strong>{{ $cust->creditLedger?->status ?? 'Settled' }}</strong>
                                    </div>
                                </div>

                                <!-- Loyalty Record -->
                                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-emerald-900">
                                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block mb-1">Loyalty Rewards</span>
                                    <div class="text-xl font-extrabold">{{ $refills }} / 10 <span class="text-xs font-normal">refills</span></div>
                                    <div class="mt-2 text-[11px] text-emerald-700">
                                        Free Jugs: <strong>{{ $cust->loyaltyRecord?->free_jugs_earned ?? 0 }} earned</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Smart Reorder Status -->
                            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-100">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-amber-900 mb-1.5">Smart Refill Forecast</h4>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-amber-900">
                                            Average cycle: <strong>{{ $cust->avg_reorder_days }} days</strong>
                                            @if ($cust->last_order_date)
                                                (Last ordered {{ now()->diffInDays($cust->last_order_date) }} days ago on {{ $cust->last_order_date->format('M d, Y') }})
                                            @endif
                                        </p>
                                    </div>
                                    <x-status-badge :status="$isDue ? 'Due for Refill' : 'Good'" />
                                </div>
                            </div>

                            <!-- Notes -->
                            @if ($cust->notes)
                                <div class="p-3 bg-slate-50 rounded-xl text-slate-600">
                                    <p class="font-bold text-slate-700 mb-0.5">Customer Notes:</p>
                                    <p>{{ $cust->notes }}</p>
                                </div>
                            @endif

                            <div class="pt-3 border-t border-slate-100 flex justify-end">
                                <button type="button" @click="$dispatch('close-modal', 'customer-detail-{{ $cust->id }}')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs">Close</button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    @endif

    <!-- Add Customer Modal -->
    <x-modal name="add-customer-modal" title="Register New Customer" maxWidth="max-w-xl">
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Maria Clara" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <input type="text" name="phone" required placeholder="0917-123-4567" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email (Optional)</label>
                    <input type="email" name="email" placeholder="customer@example.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Street Address *</label>
                <textarea name="address" rows="2" required placeholder="House No., Street, Subdivision" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Barangay</label>
                    <input type="text" name="barangay" placeholder="e.g. Brgy. San Antonio" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Area / Zone</label>
                    <input type="text" name="area" placeholder="e.g. Zone 1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Initial Jug Deposit (₱)</label>
                    <input type="number" step="0.01" name="jug_deposit" value="0.00" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Expected Reorder Cycle (Days)</label>
                    <input type="number" name="avg_reorder_days" value="7" min="1" max="365" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes</label>
                <input type="text" name="notes" placeholder="Landmarks, preferred refill schedules..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-customer-modal')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Customer</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
