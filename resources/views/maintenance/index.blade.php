<x-layouts.app title="Equipment Maintenance">
    <x-page-header title="Equipment Maintenance" subtitle="Preventive maintenance schedules for RO membranes, pumps, and UV sterilizers">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'add-equipment-modal')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Register Equipment</span>
        </button>
    </x-page-header>

    <!-- Status Overview Pills -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-extrabold text-lg shrink-0">
                {{ $overdueCount }}
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Overdue Service</p>
                <h4 class="text-sm font-bold text-slate-800">{{ $overdueCount > 0 ? 'Requires immediate action' : 'All equipment up to date' }}</h4>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-extrabold text-lg shrink-0">
                {{ $dueSoonCount }}
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Due Within 14 Days</p>
                <h4 class="text-sm font-bold text-slate-800">Scheduled for inspection</h4>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-extrabold text-lg shrink-0">
                {{ $goodCount }}
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Normal Operation</p>
                <h4 class="text-sm font-bold text-slate-800">Healthy maintenance cycles</h4>
            </div>
        </div>
    </div>

    <!-- Equipment Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @foreach ($equipment as $eq)
            @php
                $status = $eq->service_status;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-[10px] font-bold uppercase tracking-wider mb-1">
                                {{ $eq->equipment_type }}
                            </span>
                            <h3 class="text-base font-bold text-slate-900">{{ $eq->equipment_name }}</h3>
                        </div>
                        <x-status-badge :status="$status" />
                    </div>

                    <div class="grid grid-cols-2 gap-3 py-3 border-y border-slate-100 text-xs mb-4">
                        <div>
                            <span class="text-slate-400 block">Last Serviced</span>
                            <span class="font-semibold text-slate-700">{{ $eq->last_service_date ? $eq->last_service_date->format('M d, Y') : 'Not recorded' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Next Service Date</span>
                            <span class="font-bold {{ $eq->isOverdue() ? 'text-rose-600' : ($eq->isDueSoon() ? 'text-amber-600' : 'text-slate-900') }}">
                                {{ $eq->next_service_date->format('M d, Y') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Service Interval</span>
                            <span class="font-medium text-slate-700">Every {{ $eq->service_interval_days }} days</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Volume Processed</span>
                            <span class="font-medium text-slate-700">{{ $eq->volume_processed ?: 'Unmetered' }}</span>
                        </div>
                    </div>

                    @if ($eq->notes)
                        <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-600 mb-4 border border-slate-100">
                            <span class="font-bold text-slate-700 block mb-0.5">Maintenance Notes:</span>
                            <p class="whitespace-pre-line">{{ $eq->notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Mark Serviced Button -->
                <div class="pt-2" x-data>
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'service-modal-{{ $eq->id }}')"
                        class="w-full py-2.5 px-4 rounded-xl {{ $eq->isOverdue() ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-blue-50 hover:bg-blue-100 text-blue-700' }} font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Mark Serviced Today</span>
                    </button>

                    <!-- Service Confirmation Modal -->
                    <x-modal name="service-modal-{{ $eq->id }}" title="Confirm Service: {{ $eq->equipment_name }}">
                        <form method="POST" action="{{ route('maintenance.serviced', $eq) }}" class="space-y-4">
                            @csrf
                            <div class="p-3 rounded-xl bg-slate-50 text-xs">
                                <p>This will set the last service date to today (<strong>{{ now()->format('M d, Y') }}</strong>) and advance the next service date by <strong>{{ $eq->service_interval_days }} days</strong>.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Service Notes (Optional)</label>
                                <textarea name="notes" rows="3" placeholder="Replaced cartridge, checked PSI, flushed membrane..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                <button type="button" @click="$dispatch('close-modal', 'service-modal-{{ $eq->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Confirm Service</button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Equipment Modal -->
    <x-modal name="add-equipment-modal" title="Register New Machinery" maxWidth="max-w-md">
        <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Equipment Name *</label>
                <input type="text" name="equipment_name" required placeholder="e.g. Reverse Osmosis (RO) Membrane 4040" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Type *</label>
                    <select name="equipment_type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                        <option value="RO Membrane">RO Membrane</option>
                        <option value="UV Sterilizer">UV Sterilizer</option>
                        <option value="Filter">Filter Tank</option>
                        <option value="Pump">Booster Pump</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Interval (Days) *</label>
                    <input type="number" name="service_interval_days" value="90" min="1" max="730" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Install Date</label>
                    <input type="date" name="install_date" value="{{ now()->toDateString() }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Next Service Date *</label>
                    <input type="date" name="next_service_date" value="{{ now()->addDays(90)->toDateString() }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Volume Processed</label>
                <input type="text" name="volume_processed" placeholder="e.g. 10,000 Gallons" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes</label>
                <textarea name="notes" rows="2" placeholder="Specifications, model numbers, maintenance instructions..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-equipment-modal')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Equipment</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
