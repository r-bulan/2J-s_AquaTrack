<x-layouts.app title="Customer Portal">
    <!-- Top Greeting Banner -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Customer Service Portal</h1>
            <p class="text-xs text-slate-500 mt-1">Manage refill orders, recurring schedules, water gallon balances, and station feedback</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Account Verified</span>
            </span>
        </div>
    </div>

    <!-- Email Verification Alert if unverified -->
    @if (!Auth::user()->hasVerifiedEmail())
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs">
                    <strong>Email Verification Pending:</strong> Please click the link sent to your email ({{ Auth::user()->email }}) to activate refill ordering.
                </div>
            </div>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="text-xs font-bold text-amber-800 underline hover:text-amber-900 cursor-pointer">Resend Link</button>
            </form>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 mb-6">
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none">
            <a
                href="{{ route('portal.index', ['tab' => 'home']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'home' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Home Overview</span>
            </a>

            <a
                href="{{ route('portal.index', ['tab' => 'place-order']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'place-order' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Place Refill Order</span>
            </a>

            <a
                href="{{ route('portal.index', ['tab' => 'recurring']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'recurring' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Recurring Refills</span>
                @if ($recurringOrders->where('status', 'Active')->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === 'recurring' ? 'bg-white text-blue-700' : 'bg-blue-600 text-white' }}">
                        {{ $recurringOrders->where('status', 'Active')->count() }}
                    </span>
                @endif
            </a>

            <a
                href="{{ route('portal.index', ['tab' => 'orders']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'orders' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>My Orders</span>
            </a>

            <a
                href="{{ route('portal.index', ['tab' => 'feedback']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'feedback' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Feedback & Support</span>
            </a>
        </div>
    </div>

    <!-- Tab 1: Home Overview -->
    @if ($tab === 'home')
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-cyan-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-blue-500/20 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-xs mb-2">
                        Welcome back!
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $customer->name }}</h2>
                    <p class="text-blue-100 text-xs sm:text-sm mt-1">
                        Registered address: {{ $customer->address }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a
                        href="{{ route('portal.index', ['tab' => 'place-order']) }}"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-blue-700 font-bold text-xs shadow-lg hover:bg-blue-50 transition"
                    >
                        <span>Order Refill Now</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a
                        href="{{ route('portal.index', ['tab' => 'recurring']) }}"
                        class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-blue-800/60 hover:bg-blue-800 text-white font-bold text-xs border border-blue-400/30 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Manage Recurring</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Predictive Refill Reminder Card -->
        <div class="mb-8 p-5 rounded-2xl {{ $refillForecast['is_due'] ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-blue-50 border-blue-200 text-blue-900' }} border shadow-xs flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl {{ $refillForecast['is_due'] ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600' }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold">Predictive Refill Reminder</h3>
                    @if ($refillForecast['is_due'])
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900">Due Soon</span>
                    @endif
                </div>
                <p class="text-xs mt-1 leading-relaxed">{{ $refillForecast['message'] }}</p>
            </div>
            @if ($refillForecast['is_due'])
                <a href="{{ route('portal.index', ['tab' => 'place-order']) }}" class="hidden sm:inline-flex items-center px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shrink-0 shadow-xs">
                    Quick Refill
                </a>
            @endif
        </div>

        <!-- 3 Core Cards: Jugs, Credit, Loyalty -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Jugs Held (A3: Deposit display cleanly removed) -->
            <x-card title="My Water Gallons" subtitle="Jugs currently held at your location">
                <div class="text-3xl font-extrabold text-blue-600">
                    {{ $customer->jugLedger?->jugs_held ?? 0 }} <span class="text-sm font-semibold text-slate-500">containers</span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
                    <span>Clean container tracking for exchange upon next delivery.</span>
                </div>
            </x-card>

            <!-- Credit / Utang -->
            <x-card title="Outstanding Credit (Utang)" subtitle="Current water delivery balance">
                @php
                    $owed = $customer->creditLedger?->amount_owed ?? 0;
                @endphp
                <div class="text-3xl font-extrabold {{ $owed > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    ₱{{ number_format($owed, 2) }}
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between">
                        <span>Account Status:</span>
                        <span class="font-bold {{ $owed > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $customer->creditLedger?->status ?? 'Settled' }}</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Payment can be given directly to the rider upon delivery.</p>
                </div>
            </x-card>

            <!-- Loyalty Rewards -->
            <x-card title="Loyalty Milestone Rewards" subtitle="Earn free refills with every purchase">
                @php
                    $loyalty = $customer->loyaltyRecord;
                    $count = $loyalty?->refills_count ?? 0;
                    $needed = $loyalty?->refills_needed ?? 10;
                    $percent = min(100, round(($count / $needed) * 100));
                @endphp
                <div class="flex items-baseline justify-between mb-2">
                    <span class="text-2xl font-extrabold text-blue-700">{{ $count }} / {{ $needed }}</span>
                    <span class="text-xs font-bold text-slate-500">{{ $percent }}% to free refill</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs flex justify-between items-center">
                    <span class="text-slate-600">Free Gallons Earned:</span>
                    <span class="font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                        {{ $loyalty?->free_jugs_earned ?? 0 }} Free
                    </span>
                </div>
            </x-card>
        </div>

    <!-- Tab 2: Place Order (A1: Mixed Round + Flat Jugs) -->
    @elseif ($tab === 'place-order')
        <div class="max-w-xl mx-auto">
            <x-card title="Order Clean Purified Water Refill" subtitle="Choose your container quantities and delivery preferences">
                <form
                    method="POST"
                    action="{{ route('portal.orders.store') }}"
                    x-data="{
                        roundQty: 2,
                        flatQty: 0,
                        roundPrice: {{ (float) ($prices['Round'] ?? 35.00) }},
                        flatPrice: {{ (float) ($prices['Flat'] ?? 40.00) }},
                        get totalJugs() {
                            return (parseInt(this.roundQty) || 0) + (parseInt(this.flatQty) || 0);
                        },
                        get total() {
                            const roundSub = (parseInt(this.roundQty) || 0) * this.roundPrice;
                            const flatSub = (parseInt(this.flatQty) || 0) * this.flatPrice;
                            return (roundSub + flatSub).toFixed(2);
                        }
                    }"
                    class="space-y-5"
                >
                    @csrf

                    <!-- Mixed Jug Quantities -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Select Refill Quantities *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Round Gallons -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-blue-300 transition space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">Round Gallon</h4>
                                        <p class="text-[11px] text-blue-600 font-semibold" x-text="'₱' + roundPrice.toFixed(2) + ' each'"></p>
                                    </div>
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                                        20L
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <span class="text-xs text-slate-500 font-medium">Quantity:</span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            x-on:click="if (roundQty > 0) roundQty--"
                                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base flex items-center justify-center cursor-pointer transition"
                                        >-</button>
                                        <input
                                            type="number"
                                            name="round_count"
                                            x-model.number="roundQty"
                                            min="0"
                                            max="100"
                                            class="w-16 text-center py-1.5 rounded-xl border border-slate-200 font-extrabold text-sm text-slate-900"
                                        >
                                        <button
                                            type="button"
                                            x-on:click="roundQty++"
                                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base flex items-center justify-center cursor-pointer transition"
                                        >+</button>
                                    </div>
                                </div>
                                <div class="text-right text-[11px] font-bold text-slate-600">
                                    Subtotal: <span class="text-blue-700" x-text="'₱' + ((roundQty || 0) * roundPrice).toFixed(2)"></span>
                                </div>
                            </div>

                            <!-- Flat Gallons -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-blue-300 transition space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">Flat Gallon (Slim)</h4>
                                        <p class="text-[11px] text-blue-600 font-semibold" x-text="'₱' + flatPrice.toFixed(2) + ' each'"></p>
                                    </div>
                                    <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-bold text-xs">
                                        20L
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <span class="text-xs text-slate-500 font-medium">Quantity:</span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            x-on:click="if (flatQty > 0) flatQty--"
                                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base flex items-center justify-center cursor-pointer transition"
                                        >-</button>
                                        <input
                                            type="number"
                                            name="flat_count"
                                            x-model.number="flatQty"
                                            min="0"
                                            max="100"
                                            class="w-16 text-center py-1.5 rounded-xl border border-slate-200 font-extrabold text-sm text-slate-900"
                                        >
                                        <button
                                            type="button"
                                            x-on:click="flatQty++"
                                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base flex items-center justify-center cursor-pointer transition"
                                        >+</button>
                                    </div>
                                </div>
                                <div class="text-right text-[11px] font-bold text-slate-600">
                                    Subtotal: <span class="text-blue-700" x-text="'₱' + ((flatQty || 0) * flatPrice).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Calculated Price Banner -->
                    <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-blue-900">Total Refill Containers:</span>
                            <div class="text-xs text-slate-600 mt-0.5">
                                <strong class="text-slate-900" x-text="totalJugs"></strong> total (<span x-text="roundQty || 0"></span> Round + <span x-text="flatQty || 0"></span> Flat)
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Order Total</span>
                            <div class="text-2xl font-extrabold text-blue-700" x-text="'₱' + total"></div>
                        </div>
                    </div>

                    <!-- Error warning if both quantities are 0 -->
                    <div x-show="totalJugs < 1" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
                        Please select at least 1 Round or Flat gallon container.
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Payment Method *</label>
                        <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                            <option value="Cash" selected>Cash on Delivery (COD)</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Credit">Credit (Pay Later)</option>
                        </select>
                    </div>

                    <!-- Delivery Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Delivery Address</label>
                        <textarea
                            name="delivery_address"
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400"
                            placeholder="Defaults to: {{ $customer->address }}"
                        >{{ old('delivery_address', $customer->address) }}</textarea>
                    </div>

                    <!-- Preferred Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Delivery Window</label>
                        <input
                            type="text"
                            name="preferred_time"
                            placeholder="e.g. 9:00 AM - 11:00 AM"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800"
                        >
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Notes / Landmarks</label>
                        <input
                            type="text"
                            name="notes"
                            placeholder="Special delivery notes, gate code, landmarks..."
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"
                        >
                    </div>

                    <button
                        type="submit"
                        :disabled="totalJugs < 1"
                        class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs shadow-md shadow-blue-500/20 transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span>Confirm & Place Refill Order</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </x-card>
        </div>

    <!-- Tab 3: Recurring Refills (A2) -->
    @elseif ($tab === 'recurring')
        <div class="space-y-8">
            <!-- Create Recurring Refill Schedule -->
            <div class="max-w-2xl mx-auto">
                <x-card title="Set Up an Automatic Recurring Water Refill" subtitle="Never run out of drinking water. Schedule automatic refills delivered to your doorstep.">
                    <form
                        method="POST"
                        action="{{ route('portal.recurring.store') }}"
                        x-data="{
                            roundQty: 2,
                            flatQty: 0,
                            frequency: 'Weekly',
                            roundPrice: {{ (float) ($prices['Round'] ?? 35.00) }},
                            flatPrice: {{ (float) ($prices['Flat'] ?? 40.00) }},
                            get totalJugs() {
                                return (parseInt(this.roundQty) || 0) + (parseInt(this.flatQty) || 0);
                            },
                            get estTotal() {
                                const roundSub = (parseInt(this.roundQty) || 0) * this.roundPrice;
                                const flatSub = (parseInt(this.flatQty) || 0) * this.flatPrice;
                                return (roundSub + flatSub).toFixed(2);
                            }
                        }"
                        class="space-y-5"
                    >
                        @csrf

                        <!-- Mixed Quantities for Recurring -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Recurring Jug Quantities *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Round Gallons -->
                                <div class="p-3.5 rounded-2xl border border-slate-200 bg-white space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">Round Gallon</span>
                                        <span class="text-[11px] font-bold text-blue-600" x-text="'₱' + roundPrice.toFixed(2)"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-500">Containers:</span>
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-on:click="if (roundQty > 0) roundQty--" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center">-</button>
                                            <input type="number" name="round_count" x-model.number="roundQty" min="0" max="100" class="w-14 text-center py-1 rounded-lg border border-slate-200 text-xs font-bold">
                                            <button type="button" x-on:click="roundQty++" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center">+</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Flat Gallons -->
                                <div class="p-3.5 rounded-2xl border border-slate-200 bg-white space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">Flat Gallon (Slim)</span>
                                        <span class="text-[11px] font-bold text-blue-600" x-text="'₱' + flatPrice.toFixed(2)"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-500">Containers:</span>
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-on:click="if (flatQty > 0) flatQty--" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center">-</button>
                                            <input type="number" name="flat_count" x-model.number="flatQty" min="0" max="100" class="w-14 text-center py-1 rounded-lg border border-slate-200 text-xs font-bold">
                                            <button type="button" x-on:click="flatQty++" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Frequency & First Delivery Date -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Delivery Frequency *</label>
                                <select name="frequency" x-model="frequency" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 font-medium">
                                    <option value="Weekly">Weekly (Every 7 days)</option>
                                    <option value="Biweekly">Every 2 Weeks (Biweekly)</option>
                                    <option value="Monthly">Monthly</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">First Delivery Date *</label>
                                <input
                                    type="date"
                                    name="next_order_date"
                                    value="{{ now()->toDateString() }}"
                                    min="{{ now()->toDateString() }}"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 font-medium"
                                >
                            </div>
                        </div>

                        <!-- Price summary & note on dynamic pricing -->
                        <div class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-blue-900">Estimated Delivery Total:</span>
                                <p class="text-[11px] text-slate-500">Applies current station pricing when order generates.</p>
                            </div>
                            <span class="text-xl font-extrabold text-blue-700" x-text="'₱' + estTotal"></span>
                        </div>

                        <!-- Payment & Address -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Payment Method *</label>
                                <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                                    <option value="Cash" selected>Cash on Delivery (COD)</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Maya">Maya</option>
                                    <option value="Credit">Credit (Pay Later)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Window</label>
                                <input type="text" name="preferred_time" placeholder="e.g. 10:00 AM - 12:00 PM" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Delivery Address</label>
                            <textarea name="delivery_address" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">{{ old('delivery_address', $customer->address) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Schedule Notes</label>
                            <input type="text" name="notes" placeholder="Standing instructions for delivery rider..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
                        </div>

                        <button
                            type="submit"
                            :disabled="totalJugs < 1"
                            class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs shadow-md shadow-blue-500/20 transition cursor-pointer"
                        >
                            Save & Activate Recurring Schedule
                        </button>
                    </form>
                </x-card>
            </div>

            <!-- Existing Schedules List -->
            <x-card title="My Recurring Delivery Schedules" subtitle="View, pause, resume, or cancel your scheduled water refills">
                @if ($recurringOrders->isEmpty())
                    <x-empty-state message="You don't have any recurring refill schedules yet. Create one above to enjoy automated deliveries!" />
                @else
                    <div class="space-y-4">
                        @foreach ($recurringOrders as $schedule)
                            <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-xs hover:border-slate-200 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-sm text-slate-900">Schedule #{{ $schedule->id }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $schedule->status === 'Active' ? 'bg-emerald-100 text-emerald-800' : ($schedule->status === 'Paused' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                                                {{ $schedule->status }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ $schedule->frequency }}
                                            </span>
                                        </div>

                                        <div class="text-xs font-semibold text-slate-800">
                                            Load: <span class="text-blue-700 font-bold">{{ $schedule->breakdown }}</span>
                                        </div>

                                        <div class="text-xs text-slate-500 flex items-center gap-4">
                                            <span>Next Delivery: <strong class="text-slate-700">{{ $schedule->next_order_date->format('M d, Y') }}</strong></span>
                                            @if ($schedule->preferred_time)
                                                <span>Time: <strong class="text-slate-700">{{ $schedule->preferred_time }}</strong></span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Action Controls -->
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if ($schedule->status === 'Active')
                                            <form method="POST" action="{{ route('portal.recurring.pause', $schedule) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 text-xs font-bold transition cursor-pointer">
                                                    Pause
                                                </button>
                                            </form>
                                        @elseif ($schedule->status === 'Paused')
                                            <form method="POST" action="{{ route('portal.recurring.resume', $schedule) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 text-xs font-bold transition cursor-pointer">
                                                    Resume
                                                </button>
                                            </form>
                                        @endif

                                        @if ($schedule->status !== 'Cancelled')
                                            <form method="POST" action="{{ route('portal.recurring.cancel', $schedule) }}" onsubmit="return confirm('Are you sure you want to cancel this recurring schedule?');">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold transition cursor-pointer">
                                                    Cancel
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

    <!-- Tab 4: Orders History -->
    @elseif ($tab === 'orders')
        <x-card title="My Water Refill History" subtitle="Track orders, assigned riders, and delivery status">
            @if ($orders->isEmpty())
                <x-empty-state message="You haven't placed any orders yet. Click 'Place Refill Order' above to get started!" />
            @else
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-3.5 px-4 sm:px-6">Order ID</th>
                                <th class="py-3.5 px-4">Refill Containers</th>
                                <th class="py-3.5 px-4">Total</th>
                                <th class="py-3.5 px-4">Payment</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Assigned Rider</th>
                                <th class="py-3.5 px-4 sm:px-6">Order Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-800">
                                        #{{ $order->id }}
                                        @if ($order->type === 'Recurring' || $order->recurring_order_id)
                                            <div class="text-[10px] font-bold text-blue-600">Recurring</div>
                                        @else
                                            <div class="text-[10px] font-medium text-slate-400">{{ $order->type }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900">{{ $order->breakdown }}</div>
                                        @if ($order->recurring_order_id)
                                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 mt-0.5">
                                                Schedule #{{ $order->recurring_order_id }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        ₱{{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-medium text-slate-700">{{ $order->payment_method }}</span>
                                        <span class="inline-block ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold {{ $order->payment_status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $order->payment_status }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <x-status-badge :status="$order->status" />
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600">
                                        {{ $order->rider_name ?: 'Waiting Dispatch' }}
                                    </td>
                                    <td class="py-3.5 px-4 sm:px-6 text-slate-500 whitespace-nowrap">
                                        {{ $order->order_date->format('M d, Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </x-card>

    <!-- Tab 5: Feedback & Complaints -->
    @elseif ($tab === 'feedback')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Submit Feedback Form -->
            <x-card title="Send Feedback or Report an Issue" subtitle="Help us improve our water station delivery service">
                <form method="POST" action="{{ route('portal.feedback.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Submission Type *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-center p-2.5 rounded-xl border text-xs font-semibold cursor-pointer border-slate-200 hover:bg-slate-50">
                                <input type="radio" name="type" value="Feedback" checked class="mr-2 text-blue-600">
                                <span>General Feedback / Compliment</span>
                            </label>
                            <label class="flex items-center justify-center p-2.5 rounded-xl border text-xs font-semibold cursor-pointer border-slate-200 hover:bg-slate-50">
                                <input type="radio" name="type" value="Complaint" class="mr-2 text-rose-600">
                                <span>Complaint / Issue</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Experience Rating (1-5 Stars) *</label>
                        <select name="rating" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                            <option value="5" selected>★★★★★ - Excellent (5 Stars)</option>
                            <option value="4">★★★★☆ - Very Good (4 Stars)</option>
                            <option value="3">★★★☆☆ - Average (3 Stars)</option>
                            <option value="2">★★☆☆☆ - Poor (2 Stars)</option>
                            <option value="1">★☆☆☆☆ - Terrible (1 Star)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Message / Comments *</label>
                        <textarea
                            name="message"
                            rows="4"
                            required
                            placeholder="Tell us about water taste, delivery speed, container cleanliness..."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition cursor-pointer"
                    >
                        Submit Feedback
                    </button>
                </form>
            </x-card>

            <!-- Past Submissions History -->
            <x-card title="My Previous Submissions" subtitle="Review status and station responses">
                @if ($feedbackList->isEmpty())
                    <x-empty-state message="You haven't submitted any feedback yet." />
                @else
                    <div class="space-y-4">
                        @foreach ($feedbackList as $fb)
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800">{{ $fb->type }}</span>
                                        <x-star-rating :rating="$fb->rating" />
                                    </div>
                                    <x-status-badge :status="$fb->status" />
                                </div>
                                <p class="text-slate-600">{{ $fb->message }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">{{ $fb->created_at->format('M d, Y h:i A') }}</p>

                                @if ($fb->admin_response)
                                    <div class="mt-2.5 p-2.5 bg-blue-50 rounded-xl text-blue-900 border border-blue-100">
                                        <strong class="font-bold block">Station Response:</strong>
                                        <p class="mt-0.5">{{ $fb->admin_response }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.app>
