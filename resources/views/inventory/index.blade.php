<x-layouts.app title="Inventory & Supplies">
    <x-page-header title="Inventory & Supplies" subtitle="Station supplies, consumable stock levels, and threshold alerts">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'add-item-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Inventory Item</span>
        </button>
    </x-page-header>

    <!-- Categories and Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Categories Filter -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                <a
                    href="{{ route('inventory.index', ['category' => 'All', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $category === 'All' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    All Items ({{ $totalItemsCount }})
                </a>
                @foreach ($categories as $cat)
                    <a
                        href="{{ route('inventory.index', ['category' => $cat, 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $category === $cat ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        {{ $cat }}
                    </a>
                @endforeach
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('inventory.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="category" value="{{ $category }}">
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search inventory items..."
                        class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Items Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($items->isEmpty())
            <x-empty-state message="No inventory items." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Item Name</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Current Stock</th>
                            <th class="py-3.5 px-4">Threshold</th>
                            <th class="py-3.5 px-4">Cost / Unit</th>
                            <th class="py-3.5 px-4">Last Restocked</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($items as $item)
                            @php
                                $isLow = $item->isLowStock();
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition {{ $isLow ? 'bg-amber-50/20' : '' }}">
                                <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-800">
                                    {{ $item->name }}
                                    @if ($isLow)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 animate-pulse">
                                            Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    {{ $item->quantity }} <span class="font-normal text-slate-500">{{ $item->unit }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ $item->reorder_threshold }} {{ $item->unit }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-700">
                                    ₱{{ number_format($item->cost_per_unit, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ $item->last_restocked ? $item->last_restocked->format('M d, Y') : 'Never' }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap" x-data>
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', 'restock-modal-{{ $item->id }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs transition cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Restock</span>
                                    </button>

                                    <!-- Restock Modal for this item -->
                                    <x-modal name="restock-modal-{{ $item->id }}" title="Restock: {{ $item->name }}">
                                        <form method="POST" action="{{ route('inventory.restock', $item) }}" class="space-y-4">
                                            @csrf
                                            <div class="p-3 bg-slate-50 rounded-xl text-xs">
                                                <p><strong>Current Stock:</strong> {{ $item->quantity }} {{ $item->unit }}</p>
                                                <p class="text-slate-500 mt-0.5">Reorder Threshold: {{ $item->reorder_threshold }} {{ $item->unit }}</p>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Quantity to Add ({{ $item->unit }}) *</label>
                                                <input type="number" name="quantity" min="1" required placeholder="e.g. 50" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes (Optional)</label>
                                                <input type="text" name="notes" placeholder="Supplier invoice, delivery batch..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
                                            </div>

                                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                                <button type="button" @click="$dispatch('close-modal', 'restock-modal-{{ $item->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Confirm Restock</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Add Item Modal -->
    <x-modal name="add-item-modal" title="Add Inventory Item" maxWidth="max-w-md">
        <form method="POST" action="{{ route('inventory.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Item Name *</label>
                <input type="text" name="name" required placeholder="e.g. 5-Gallon Polycarbonate Jug" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Category *</label>
                    <select name="category" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Unit of Measurement *</label>
                    <input type="text" name="unit" required placeholder="pcs, bags, packs" value="pcs" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Initial Quantity *</label>
                    <input type="number" name="quantity" min="0" required value="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Reorder Threshold *</label>
                    <input type="number" name="reorder_threshold" min="0" required value="10" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Cost per Unit (₱)</label>
                    <input type="number" step="0.01" name="cost_per_unit" value="0.00" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Last Restocked</label>
                    <input type="date" name="last_restocked" value="{{ now()->toDateString() }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-item-modal')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Item</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
