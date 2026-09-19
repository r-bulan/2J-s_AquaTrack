<x-layouts.app title="Riders Management">
    <x-page-header title="Riders Management" subtitle="Manage delivery riders, route territories, daily wage rates, and cash advances">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'add-rider-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Register New Rider</span>
        </button>
    </x-page-header>

    <!-- Top Summary Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <x-stat-card
            title="Active Delivery Riders"
            value="{{ $activeRidersCount }} of {{ $riders->count() }}"
            subtext="Available for route assignment"
            iconBg="bg-blue-50 text-blue-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Total Outstanding Cash Advances"
            value="₱{{ number_format($totalCashAdvance, 2) }}"
            subtext="All active and inactive riders combined"
            iconBg="bg-amber-50 text-amber-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Rider Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @foreach ($riders as $rider)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 hover:shadow-md transition flex flex-col justify-between" x-data>
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-extrabold text-base">
                                {{ strtoupper(substr($rider->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ $rider->name }}</h3>
                                <p class="text-xs text-slate-500">{{ $rider->user?->email }} • {{ $rider->phone }}</p>
                            </div>
                        </div>
                        <x-status-badge :status="$rider->status" />
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs text-slate-600 mb-4 border border-slate-100">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Assigned Area:</span>
                            <span class="font-bold text-slate-800">{{ $rider->area ?: 'General / All Zones' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Vehicle:</span>
                            <span class="font-medium text-slate-800">{{ $rider->vehicle ?: 'Station Vehicle' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Daily Wage Rate:</span>
                            <span class="font-bold text-blue-700">₱{{ number_format($rider->wage_rate, 2) }} / day</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Cash Advance Balance:</span>
                            <span class="font-bold {{ $rider->cash_advance > 0 ? 'text-amber-700' : 'text-slate-800' }}">₱{{ number_format($rider->cash_advance, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Lifetime Deliveries:</span>
                            <span class="font-bold text-slate-800">{{ $rider->deliveries_count }} completed</span>
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <form method="POST" action="{{ route('riders.toggle-status', $rider) }}">
                        @csrf
                        <button
                            type="submit"
                            class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition {{ $rider->status === 'Active' ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}"
                        >
                            {{ $rider->status === 'Active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'advance-modal-{{ $rider->id }}')"
                            class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 hover:bg-amber-100 font-semibold text-xs transition"
                        >
                            Cash Advance
                        </button>

                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'edit-rider-modal-{{ $rider->id }}')"
                            class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold text-xs transition"
                        >
                            Edit
                        </button>
                    </div>
                </div>

                <!-- Edit Rider Modal -->
                <x-modal name="edit-rider-modal-{{ $rider->id }}" title="Edit Rider: {{ $rider->name }}">
                    <form method="POST" action="{{ route('riders.update', $rider) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                            <input type="text" name="name" value="{{ $rider->name }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Phone *</label>
                                <input type="text" name="phone" value="{{ $rider->phone }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status *</label>
                                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                                    <option value="Active" {{ $rider->status === 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $rider->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Wage Rate (₱/day) *</label>
                                <input type="number" step="0.01" name="wage_rate" value="{{ $rider->wage_rate }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Cash Advance (₱)</label>
                                <input type="number" step="0.01" name="cash_advance" value="{{ $rider->cash_advance }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Territory / Area</label>
                            <input type="text" name="area" value="{{ $rider->area }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Assigned Vehicle</label>
                            <input type="text" name="vehicle" value="{{ $rider->vehicle }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="$dispatch('close-modal', 'edit-rider-modal-{{ $rider->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Changes</button>
                        </div>
                    </form>
                </x-modal>

                <!-- Cash Advance Adjustment Modal -->
                <x-modal name="advance-modal-{{ $rider->id }}" title="Cash Advance: {{ $rider->name }}">
                    <form method="POST" action="{{ route('riders.cash-advance', $rider) }}" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-slate-50 rounded-xl text-xs">
                            <p>Current Advance Balance: <strong class="text-amber-800">₱{{ number_format($rider->cash_advance, 2) }}</strong></p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Operation *</label>
                            <select name="adjustment_type" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                                <option value="add">Add Cash Advance (+)</option>
                                <option value="deduct">Deduct / Repay Cash Advance (-)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Amount (₱) *</label>
                            <input type="number" step="0.01" name="amount" min="0.01" required placeholder="0.00" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes (Optional)</label>
                            <input type="text" name="notes" placeholder="e.g. Fuel allowance, emergency cash, payroll deduction..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="$dispatch('close-modal', 'advance-modal-{{ $rider->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold">Update Advance</button>
                        </div>
                    </form>
                </x-modal>
            </div>
        @endforeach
    </div>

    <!-- Section 37: Rider Payroll & Cash Advance Table -->
    <x-card title="Rider Payroll & Cash Advance Summary" subtitle="Staff role removed: Dedicated rider wage records and outstanding balances">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Rider Name</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Wage Rate (₱)</th>
                        <th class="py-3 px-4">Outstanding Cash Advance</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach ($riders as $r)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $r->name }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                    Rider
                                </span>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">₱{{ number_format($r->wage_rate, 2) }} / day</td>
                            <td class="py-3 px-4 font-bold {{ $r->cash_advance > 0 ? 'text-amber-700' : 'text-slate-600' }}">₱{{ number_format($r->cash_advance, 2) }}</td>
                            <td class="py-3 px-4">
                                <x-status-badge :status="$r->status" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Register Rider Modal -->
    <x-modal name="add-rider-modal" title="Register New Delivery Rider" maxWidth="max-w-lg">
        <form method="POST" action="{{ route('riders.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Kuya Mark Santos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email (Login) *</label>
                    <input type="email" name="email" required placeholder="rider@twojs.test" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <input type="text" name="phone" required placeholder="0918-123-4567" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Password *</label>
                <input type="password" name="password" required placeholder="Minimum 8 characters" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Daily Wage Rate (₱) *</label>
                    <input type="number" step="0.01" name="wage_rate" required value="450.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Initial Cash Advance (₱)</label>
                    <input type="number" step="0.01" name="cash_advance" value="0.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Assigned Area</label>
                    <input type="text" name="area" placeholder="e.g. Zone 1 & Zone 2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status *</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                        <option value="Active" selected>Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Vehicle Details</label>
                <input type="text" name="vehicle" placeholder="e.g. Honda Wave 110 w/ Sidecar" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-rider-modal')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Rider</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
