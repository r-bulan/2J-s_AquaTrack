<x-layouts.app title="Station Settings">
    <x-page-header title="Station Settings" subtitle="Configure system parameters, gallon refill pricing, and business rules" />

    <div class="max-w-2xl space-y-6">
        <!-- Gallon Pricing Card -->
        <x-card title="Refill Gallon Pricing" subtitle="Separate rates for Round Gallon and Slim/Flat Gallon containers">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                @csrf

                <!-- Pricing Notice Alert -->
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong class="font-bold">Historical Price Protection Rule:</strong>
                        Changing prices will automatically apply to all <em>new</em> walk-in, phone, and portal customer orders. Historical orders preserve their original unit price snapshot and calculated total.
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Round Gallon Price -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                            <label for="round_price" class="text-xs font-bold text-slate-800 uppercase tracking-wider">Round Gallon Price</label>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">Standard 5-gallon circular polycarbonate container</p>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-sm font-bold text-slate-500">₱</span>
                            <input
                                id="round_price"
                                type="number"
                                step="0.50"
                                min="1.00"
                                max="1000.00"
                                name="round_gallon_price"
                                value="{{ old('round_gallon_price', number_format($prices['Round'] ?? 35, 2, '.', '')) }}"
                                required
                                class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-extrabold text-slate-900 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 bg-white"
                            >
                        </div>
                    </div>

                    <!-- Flat Gallon Price -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
                            <label for="flat_price" class="text-xs font-bold text-slate-800 uppercase tracking-wider">Flat Gallon Price</label>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">Slim / rectangle dispenser container</p>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-sm font-bold text-slate-500">₱</span>
                            <input
                                id="flat_price"
                                type="number"
                                step="0.50"
                                min="1.00"
                                max="1000.00"
                                name="flat_gallon_price"
                                value="{{ old('flat_gallon_price', number_format($prices['Flat'] ?? 40, 2, '.', '')) }}"
                                required
                                class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-extrabold text-slate-900 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 bg-white"
                            >
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button
                        type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition cursor-pointer"
                    >
                        Save Pricing Settings
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
