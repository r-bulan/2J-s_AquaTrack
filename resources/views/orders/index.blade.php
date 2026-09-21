<x-layouts.app title="Orders Management">
    <x-page-header title="Orders Management" subtitle="Track customer refills, assign riders, and record payments">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'new-order-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Create New Order</span>
        </button>
    </x-page-header>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Status Tabs (Horizontally scrollable on mobile) -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                @foreach (['All', 'Pending', 'Confirmed', 'Out for Delivery', 'Delivered', 'Cancelled'] as $st)
                    <a
                        href="{{ route('orders.index', ['status' => $st, 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $status === $st ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        {{ $st }}
                        <span class="ml-1 opacity-75">({{ $statusCounts[$st] ?? 0 }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Search Box -->
            <form method="GET" action="{{ route('orders.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search order #, customer..."
                        class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                @if ($search)
                    <a href="{{ route('orders.index', ['status' => $status]) }}" class="text-xs text-slate-500 hover:text-slate-800 p-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Orders Table (Horizontally scrollable on mobile/tablet) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($orders->isEmpty())
            <x-empty-state message="No orders found matching your criteria.">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'new-order-modal')"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"
                >
                    Create First Order
                </button>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Order ID</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Gallons</th>
                            <th class="py-3.5 px-4">Total</th>
                            <th class="py-3.5 px-4">Payment</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Rider</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($orders as $order)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-800">
                                    #{{ $order->id }}
                                    @if ($order->type === 'Recurring' || $order->recurring_order_id)
                                        <div class="text-[10px] font-extrabold text-blue-600">Recurring</div>
                                    @else
                                        <div class="text-[10px] font-medium text-slate-400 mt-0.5">{{ $order->type }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $order->customer_name }}</div>
                                    <div class="text-[11px] text-slate-500 truncate max-w-xs" title="{{ $order->delivery_address }}">
                                        {{ $order->delivery_address }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $order->breakdown }}</div>
                                    @if ($order->round_count > 0 && $order->flat_count > 0)
                                        <div class="text-[10px] text-slate-500 font-medium">
                                            {{ $order->round_count }} Round (@ ₱{{ number_format($order->round_unit_price, 2) }}) + {{ $order->flat_count }} Flat (@ ₱{{ number_format($order->flat_unit_price, 2) }})
                                        </div>
                                    @else
                                        <div class="text-[11px] text-blue-600 font-semibold">@ ₱{{ number_format($order->unit_price, 2) }}/jug</div>
                                    @endif
                                    @if ($order->recurring_order_id)
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            Schedule #{{ $order->recurring_order_id }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    ₱{{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-700">{{ $order->payment_method }}</div>
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold {{ $order->payment_status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : ($order->payment_status === 'Credit' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <x-status-badge :status="$order->status" />
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($order->rider_name)
                                        <div class="font-medium text-slate-800 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            {{ $order->rider_name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">Route #{{ $order->route_order }}</div>
                                    @else
                                        <span class="text-slate-400 italic">Unassigned</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    {{ $order->order_date->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap" x-data>
                                    @if (in_array($order->status, ['Pending', 'Confirmed']))
                                        <button
                                            type="button"
                                            @click="$dispatch('open-modal', 'assign-modal-{{ $order->id }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>{{ $order->rider_id ? 'Reassign' : 'Assign Rider' }}</span>
                                        </button>

                                        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="inline-block ml-1" onsubmit="return confirm('Cancel this order?');">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Cancel Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>

                                        <!-- Assign Rider Modal for this order -->
                                        <x-modal name="assign-modal-{{ $order->id }}" title="Assign Rider to Order #{{ $order->id }}">
                                            <form method="POST" action="{{ route('orders.assign', $order) }}" class="space-y-4">
                                                @csrf
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs">
                                                    <p><strong>Customer:</strong> {{ $order->customer_name }}</p>
                                                    <p class="mt-0.5 text-slate-500"><strong>Address:</strong> {{ $order->delivery_address }}</p>
                                                    <p class="mt-0.5 text-blue-600 font-semibold"><strong>Load:</strong> {{ $order->breakdown }}</p>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Select Rider</label>
                                                    <select name="rider_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                                        <option value="">-- Choose Active Rider --</option>
                                                        @foreach ($riders as $r)
                                                            <option value="{{ $r->id }}" {{ $order->rider_id == $r->id ? 'selected' : '' }}>
                                                                {{ $r->name }} ({{ $r->vehicle ?? 'No Vehicle' }} • {{ $r->area ?? 'General' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Route Queue Order</label>
                                                    <input type="number" name="route_order" value="{{ $order->route_order ?: 1 }}" min="1" max="99" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                                                    <p class="text-[11px] text-slate-400 mt-1">Lower numbers will be delivered first in the queue.</p>
                                                </div>

                                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                                    <button type="button" @click="$dispatch('close-modal', 'assign-modal-{{ $order->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Confirm Assignment</button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    @elseif ($order->status === 'Delivered')
                                        <span class="text-xs font-semibold text-emerald-600 inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Completed
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">{{ $order->status }}</span>
                                    @endif
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

    <!-- Create New Order Modal -->
    <x-modal name="new-order-modal" title="Create Station Order" maxWidth="max-w-xl">
        <form
            method="POST"
            action="{{ route('orders.store') }}"
            x-data="{
                customerId: '',
                customersData: {{ Js::from($customers->keyBy('id')) }},
                roundCount: 1,
                flatCount: 0,
                prices: {{ Js::from($prices) }},
                get roundPrice() {
                    return this.prices['Round'] || 35.00;
                },
                get flatPrice() {
                    return this.prices['Flat'] || 40.00;
                },
                get totalJugs() {
                    return (parseInt(this.roundCount) || 0) + (parseInt(this.flatCount) || 0);
                },
                get total() {
                    const r = (parseInt(this.roundCount) || 0) * this.roundPrice;
                    const f = (parseInt(this.flatCount) || 0) * this.flatPrice;
                    return (r + f).toFixed(2);
                },
                updateAddress() {
                    if (this.customerId && this.customersData[this.customerId]) {
                        document.getElementById('modal_delivery_address').value = this.customersData[this.customerId].address || '';
                    }
                }
            }"
            class="space-y-4"
        >
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Customer *</label>
                <select
                    name="customer_id"
                    x-model="customerId"
                    x-on:change="updateAddress()"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">-- Choose Customer --</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }} • {{ $c->area ?? 'General' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Order Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                        <option value="Walk-in">Walk-in</option>
                        <option value="Phone" selected>Phone</option>
                        <option value="Online">Online</option>
                        <option value="Recurring">Recurring</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Order Date *</label>
                    <input type="date" name="order_date" value="{{ now()->toDateString() }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <!-- Mixed Gallon Quantities Section with Dynamic Live Pricing -->
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 space-y-3">
                <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider">Container Quantities *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Round Gallon Quantity -->
                    <div class="p-3 bg-white rounded-xl border border-blue-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800">Round Gallon</span>
                                <span class="text-[11px] text-blue-600 block" x-text="'₱' + roundPrice.toFixed(2) + '/jug'"></span>
                            </div>
                            <span class="text-[10px] uppercase font-bold text-blue-500 bg-blue-50 px-1.5 py-0.5 rounded">20L Round</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Quantity:</span>
                            <input
                                type="number"
                                name="round_count"
                                x-model.number="roundCount"
                                min="0"
                                max="500"
                                class="w-20 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-900 text-center"
                            >
                        </div>
                    </div>

                    <!-- Flat Gallon Quantity -->
                    <div class="p-3 bg-white rounded-xl border border-blue-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800">Flat Gallon (Slim)</span>
                                <span class="text-[11px] text-blue-600 block" x-text="'₱' + flatPrice.toFixed(2) + '/jug'"></span>
                            </div>
                            <span class="text-[10px] uppercase font-bold text-cyan-600 bg-cyan-50 px-1.5 py-0.5 rounded">20L Flat</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Quantity:</span>
                            <input
                                type="number"
                                name="flat_count"
                                x-model.number="flatCount"
                                min="0"
                                max="500"
                                class="w-20 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-900 text-center"
                            >
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown Banner -->
                <div class="flex items-center justify-between pt-2 border-t border-blue-100 text-xs">
                    <span class="text-blue-800 font-semibold">
                        Total Jugs: <strong x-text="totalJugs"></strong> (<span x-text="roundCount || 0"></span> Round + <span x-text="flatCount || 0"></span> Flat)
                    </span>
                    <span class="text-slate-800 font-bold">
                        Calculated Total: <span class="text-base text-blue-700 font-extrabold" x-text="'₱' + total"></span>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                        <option value="Cash" selected>Cash on Delivery</option>
                        <option value="GCash">GCash</option>
                        <option value="Maya">Maya</option>
                        <option value="Credit">Credit (Utang)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Time</label>
                    <input type="text" name="preferred_time" placeholder="e.g. 10:00 AM - 12:00 PM" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Delivery Address</label>
                <textarea
                    id="modal_delivery_address"
                    name="delivery_address"
                    rows="2"
                    placeholder="Auto-fills from customer's default address if left blank"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"
                ></textarea>
                <p class="text-[11px] text-slate-400 mt-0.5">Leave blank to use customer's registered profile address automatically.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes / Special Instructions</label>
                <input type="text" name="notes" placeholder="Gate color, landmarks, special requests..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'new-order-modal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" :disabled="totalJugs < 1" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-md shadow-blue-500/20">Create Order</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
