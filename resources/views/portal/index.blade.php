<x-layouts.app title="Customer Portal">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Customer Refill Portal</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your Two J's purified water deliveries, refills, loyalty rewards, and orders</p>
    </div>

    <!-- Portal 4 Navigation Tabs -->
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
                <div class="shrink-0">
                    <a
                        href="{{ route('portal.index', ['tab' => 'place-order']) }}"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-blue-700 font-bold text-xs shadow-lg hover:bg-blue-50 transition"
                    >
                        <span>Order Refill Now</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
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
            <!-- Jugs Held -->
            <x-card title="My Water Gallons" subtitle="Jugs currently held at your location">
                <div class="text-3xl font-extrabold text-blue-600">
                    {{ $customer->jugLedger?->jugs_held ?? 0 }} <span class="text-sm font-semibold text-slate-500">containers</span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between">
                        <span>Deposit Status:</span>
                        <strong class="text-slate-800">{{ $customer->jugLedger?->deposit_status ?? 'Unpaid' }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Deposit Paid:</span>
                        <strong class="text-slate-800">₱{{ number_format($customer->jugLedger?->deposit_amount ?? 0, 2) }}</strong>
                    </div>
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

            <!-- Loyalty Progress -->
            <x-card title="Refill Loyalty Reward" subtitle="1 Free 5-Gallon Refill every 10 Refills">
                @php
                    $refills = $customer->loyaltyRecord?->refills_count ?? 0;
                    $needed = $customer->loyaltyRecord?->refills_needed ?? 10;
                    $percentage = min(100, round(($refills / max(1, $needed)) * 100));
                @endphp
                <div class="text-3xl font-extrabold text-emerald-600">
                    {{ $refills }} / {{ $needed }}
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-2.5 mt-3 overflow-hidden">
                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-600 flex justify-between items-center">
                    <span>Free Jugs Earned:</span>
                    <strong class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">{{ $customer->loyaltyRecord?->free_jugs_earned ?? 0 }} free gallons</strong>
                </div>
            </x-card>
        </div>

    <!-- Tab 2: Place Order -->
    @elseif ($tab === 'place-order')
        <div class="max-w-xl mx-auto">
            <x-card title="Order Clean Purified Water Refill" subtitle="Choose your container type and delivery preferences">
                <form
                    method="POST"
                    action="{{ route('portal.orders.store') }}"
                    x-data="{
                        gallonType: 'Round',
                        quantity: 2,
                        prices: {{ Js::from($prices) }},
                        get unitPrice() {
                            return this.prices[this.gallonType] || 35.00;
                        },
                        get total() {
                            return (this.quantity * this.unitPrice).toFixed(2);
                        }
                    }"
                    class="space-y-5"
                >
                    @csrf

                    <!-- Gallon Type Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Select Container Type *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="p-4 rounded-2xl border text-center cursor-pointer transition flex flex-col items-center justify-center gap-1.5"
                                :class="gallonType === 'Round' ? 'bg-blue-50 border-blue-500 text-blue-900 ring-2 ring-blue-500/20' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            >
                                <input type="radio" name="gallon_type" value="Round" x-model="gallonType" class="sr-only">
                                <span class="font-bold text-sm">Round Gallon</span>
                                <span class="text-xs font-extrabold text-blue-600" x-text="'₱' + (prices['Round'] || 35.00).toFixed(2) + ' each'"></span>
                            </label>

                            <label
                                class="p-4 rounded-2xl border text-center cursor-pointer transition flex flex-col items-center justify-center gap-1.5"
                                :class="gallonType === 'Flat' ? 'bg-blue-50 border-blue-500 text-blue-900 ring-2 ring-blue-500/20' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            >
                                <input type="radio" name="gallon_type" value="Flat" x-model="gallonType" class="sr-only">
                                <span class="font-bold text-sm">Flat Gallon (Slim)</span>
                                <span class="text-xs font-extrabold text-blue-600" x-text="'₱' + (prices['Flat'] || 40.00).toFixed(2) + ' each'"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Quantity Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gallon Quantity *</label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                x-on:click="if (quantity > 1) quantity--"
                                class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg flex items-center justify-center cursor-pointer"
                            >-</button>
                            <input
                                type="number"
                                name="jug_count"
                                x-model.number="quantity"
                                min="1"
                                max="100"
                                required
                                class="w-24 text-center py-2 rounded-xl border border-slate-200 font-extrabold text-base text-slate-900"
                            >
                            <button
                                type="button"
                                x-on:click="quantity++"
                                class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg flex items-center justify-center cursor-pointer"
                            >+</button>
                        </div>
                    </div>

                    <!-- Live Calculated Price Banner -->
                    <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-blue-800">Total Price:</span>
                            <div class="text-xs text-slate-500">
                                <span x-text="quantity"></span> x <span x-text="'₱' + unitPrice.toFixed(2)"></span>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-blue-700" x-text="'₱' + total"></div>
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
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800"
                        >{{ $customer->address }}</textarea>
                    </div>

                    <!-- Preferred Time & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Time</label>
                            <input type="text" name="preferred_time" placeholder="e.g. Before 12 PM" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Notes for Rider</label>
                            <input type="text" name="notes" placeholder="Gate bell, landmarks..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Confirm and Place Order</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </x-card>
        </div>

    <!-- Tab 3: My Orders -->
    @elseif ($tab === 'orders')
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            @if ($orders->isEmpty())
                <x-empty-state message="You have not placed any orders yet.">
                    <a href="{{ route('portal.index', ['tab' => 'place-order']) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold">
                        Place Your First Order
                    </a>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">Order ID</th>
                                <th class="py-3.5 px-4">Gallons</th>
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
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-slate-900">{{ $order->jug_count }}x</span>
                                        <span class="text-blue-600 font-semibold">{{ $order->gallon_type }} Gallon</span>
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

                <div class="p-4 border-t border-slate-100">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    <!-- Tab 4: Feedback -->
    @elseif ($tab === 'feedback')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
