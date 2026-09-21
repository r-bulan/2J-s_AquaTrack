<x-layouts.app title="Deliveries Management">
    <x-page-header
        title="{{ $isRider ? 'My Delivery Route' : 'Station Deliveries' }}"
        subtitle="{{ $isRider ? 'Your assigned refill delivery queue ordered by route sequence' : 'Real-time rider dispatch, active route progress, proof-of-delivery, and failed delivery resolution' }}"
    />

    <!-- Tabs & Filter Navigation -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                <a
                    href="{{ route('deliveries.index', ['tab' => 'queue', 'rider_id' => request('rider_id')]) }}"
                    class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $tab === 'queue' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Route Queue</span>
                    @if ($queueCount > 0)
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ $tab === 'queue' ? 'bg-white text-blue-700' : 'bg-blue-600 text-white' }}">
                            {{ $queueCount }}
                        </span>
                    @endif
                </a>

                @if (!$isRider)
                    <a
                        href="{{ route('deliveries.index', ['tab' => 'failed', 'rider_id' => request('rider_id')]) }}"
                        class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $tab === 'failed' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Failed Deliveries</span>
                        @if ($failedCount > 0)
                            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ $tab === 'failed' ? 'bg-white text-rose-600' : 'bg-rose-600 text-white' }}">
                                {{ $failedCount }}
                            </span>
                        @endif
                    </a>
                @endif

                <a
                    href="{{ route('deliveries.index', ['tab' => 'delivered', 'rider_id' => request('rider_id')]) }}"
                    class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $tab === 'delivered' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Delivered History</span>
                </a>

                @if (!$isRider)
                    <a
                        href="{{ route('deliveries.index', ['tab' => 'all', 'rider_id' => request('rider_id')]) }}"
                        class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $tab === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        <span>All Dispatches</span>
                    </a>
                @endif
            </div>

            @if (!$isRider && $riders->isNotEmpty())
                <form method="GET" action="{{ route('deliveries.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <select name="rider_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-700">
                        <option value="">-- All Riders --</option>
                        @foreach ($riders as $r)
                            <option value="{{ $r->id }}" {{ request('rider_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    <!-- Active Route Cards -->
    @if ($tab === 'queue')
        @if ($deliveries->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 text-center">
                <x-empty-state message="No active deliveries in this queue." />
            </div>
        @else
            <div class="space-y-4">
                @foreach ($deliveries as $del)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <!-- Route Number & Customer Info -->
                            <div class="flex items-start gap-3.5 min-w-0">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 border border-blue-100 flex flex-col items-center justify-center shrink-0">
                                    <span class="text-[10px] uppercase font-bold text-blue-500">Route</span>
                                    <span class="text-base font-extrabold leading-none">#{{ $del->route_order ?? 99 }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base font-bold text-slate-900">{{ $del->customer_name }}</h3>
                                        <x-status-badge :status="$del->status" />
                                        <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                            {{ $del->breakdown }}
                                        </span>
                                        @if ($del->retry_count > 0)
                                            <span class="text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                                Retry #{{ $del->retry_count }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-slate-600 mt-1 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">{{ $del->address }} ({{ $del->area ?? 'General' }})</span>
                                    </p>

                                    <div class="flex items-center gap-4 text-xs text-slate-500 mt-1.5 flex-wrap">
                                        @if ($del->customer_phone)
                                            <a href="tel:{{ $del->customer_phone }}" class="text-blue-600 hover:underline flex items-center gap-1 font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                {{ $del->customer_phone }}
                                            </a>
                                        @endif

                                        @if (!$isRider && $del->rider_name)
                                            <span class="text-slate-600 font-medium">Rider: <strong>{{ $del->rider_name }}</strong></span>
                                        @endif

                                        @if ($del->order?->preferred_time)
                                            <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md font-medium text-[11px]">
                                                Time: {{ $del->order->preferred_time }}
                                            </span>
                                        @endif

                                        <span class="text-slate-700 font-bold">
                                            Total: ₱{{ number_format($del->order?->total_amount ?? 0, 2) }} ({{ $del->order?->payment_method }})
                                        </span>
                                    </div>

                                    @if ($del->notes)
                                        <p class="mt-2 text-xs bg-slate-50 p-2 rounded-xl text-slate-600 border border-slate-100 italic">
                                            "{{ $del->notes }}"
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Controls -->
                            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center" x-data>
                                @if ($del->status === 'Assigned')
                                    <form method="POST" action="{{ route('deliveries.start', $del) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="min-h-[42px] px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-2 cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span>Start Delivery</span>
                                        </button>
                                    </form>
                                @elseif ($del->status === 'En Route')
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', 'proof-modal-{{ $del->id }}')"
                                        class="min-h-[42px] px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center gap-2 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Mark Delivered</span>
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    @click="$dispatch('open-modal', 'fail-modal-{{ $del->id }}')"
                                    class="min-h-[42px] px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-rose-50 hover:text-rose-700 text-slate-500 font-semibold text-xs transition cursor-pointer"
                                    title="Report Failed Delivery"
                                >
                                    Report Failed
                                </button>
                            </div>
                        </div>

                        <!-- Proof of Delivery Modal -->
                        <x-modal name="proof-modal-{{ $del->id }}" title="Proof of Delivery - Order #{{ $del->order_id }}" maxWidth="max-w-lg">
                            <form
                                method="POST"
                                action="{{ route('deliveries.complete', $del) }}"
                                enctype="multipart/form-data"
                                x-data="{
                                    canvas: null,
                                    ctx: null,
                                    drawing: false,
                                    hasSignature: false,
                                    signatureData: '',
                                    init() {
                                        this.$nextTick(() => {
                                            this.canvas = document.getElementById('signature-canvas-{{ $del->id }}');
                                            if (this.canvas) {
                                                this.ctx = this.canvas.getContext('2d');
                                                this.ctx.lineWidth = 2.5;
                                                this.ctx.lineCap = 'round';
                                                this.ctx.strokeStyle = '#0f172a';
                                            }
                                        });
                                    },
                                    startDraw(e) {
                                        this.drawing = true;
                                        const rect = this.canvas.getBoundingClientRect();
                                        const x = (e.clientX || (e.touches && e.touches[0].clientX)) - rect.left;
                                        const y = (e.clientY || (e.touches && e.touches[0].clientY)) - rect.top;
                                        this.ctx.beginPath();
                                        this.ctx.moveTo(x, y);
                                    },
                                    draw(e) {
                                        if (!this.drawing) return;
                                        e.preventDefault();
                                        const rect = this.canvas.getBoundingClientRect();
                                        const x = (e.clientX || (e.touches && e.touches[0].clientX)) - rect.left;
                                        const y = (e.clientY || (e.touches && e.touches[0].clientY)) - rect.top;
                                        this.ctx.lineTo(x, y);
                                        this.ctx.stroke();
                                        this.hasSignature = true;
                                    },
                                    stopDraw() {
                                        if (this.drawing) {
                                            this.drawing = false;
                                            this.signatureData = this.canvas.toDataURL('image/png');
                                            document.getElementById('sig-input-{{ $del->id }}').value = this.signatureData;
                                        }
                                    },
                                    clearCanvas() {
                                        if (this.ctx && this.canvas) {
                                            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                            this.hasSignature = false;
                                            this.signatureData = '';
                                            document.getElementById('sig-input-{{ $del->id }}').value = '';
                                        }
                                    }
                                }"
                                class="space-y-4"
                            >
                                @csrf
                                <input type="hidden" name="signature" id="sig-input-{{ $del->id }}">

                                <div class="bg-blue-50/75 p-3 rounded-xl border border-blue-100 text-xs">
                                    <p class="font-bold text-blue-900">Refill Delivery Details:</p>
                                    <p class="text-blue-800">{{ $del->customer_name }} • {{ $del->breakdown }}</p>
                                    <p class="text-blue-700 font-extrabold mt-0.5">Total to Collect: ₱{{ number_format($del->order?->total_amount ?? 0, 2) }} ({{ $del->order?->payment_method }})</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Delivery Photo Proof (Camera / File)
                                    </label>
                                    <input
                                        type="file"
                                        name="proof_photo"
                                        accept="image/*"
                                        capture="environment"
                                        class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                    >
                                    <p class="text-[11px] text-slate-400 mt-1">Upload actual photo of delivered gallons at customer location.</p>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Customer E-Signature</label>
                                        <button
                                            type="button"
                                            x-on:click="clearCanvas()"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700"
                                        >
                                            Clear Signature
                                        </button>
                                    </div>
                                    <div class="border border-slate-200 rounded-xl bg-slate-50 overflow-hidden touch-none">
                                        <canvas
                                            id="signature-canvas-{{ $del->id }}"
                                            width="440"
                                            height="140"
                                            x-on:mousedown="startDraw($event)"
                                            x-on:mousemove="draw($event)"
                                            x-on:mouseup="stopDraw()"
                                            x-on:mouseleave="stopDraw()"
                                            x-on:touchstart="startDraw($event)"
                                            x-on:touchmove="draw($event)"
                                            x-on:touchend="stopDraw()"
                                            class="w-full h-32 bg-white cursor-crosshair"
                                        ></canvas>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Customer may sign with finger on mobile or mouse on desktop.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Empty Jugs Returned</label>
                                        <input type="number" name="returned_jugs" min="0" value="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Delivery Notes</label>
                                        <input type="text" name="notes" placeholder="Received by customer..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" @click="$dispatch('close-modal', 'proof-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20">Complete & Synchronize</button>
                                </div>
                            </form>
                        </x-modal>

                        <!-- Mark Failed Modal -->
                        <x-modal name="fail-modal-{{ $del->id }}" title="Report Failed Delivery - Order #{{ $del->order_id }}">
                            <form method="POST" action="{{ route('deliveries.fail', $del) }}" class="space-y-4" x-data="{ reason: '' }">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Reason for Failure *</label>
                                    <select name="reason" x-model="reason" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800">
                                        <option value="">-- Select Predefined Reason --</option>
                                        @foreach (\App\Models\Delivery::FAILURE_REASONS as $r)
                                            <option value="{{ $r }}">{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Failure Notes <span x-show="reason === 'Other'" class="text-rose-500 font-bold">* (Required for 'Other')</span>
                                    </label>
                                    <textarea
                                        name="notes"
                                        rows="3"
                                        :required="reason === 'Other'"
                                        placeholder="Detailed explanation (e.g. gate locked, customer requested callback tomorrow)..."
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800"
                                    ></textarea>
                                </div>
                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" @click="$dispatch('close-modal', 'fail-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold">Submit Failure Report</button>
                                </div>
                            </form>
                        </x-modal>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Failed Deliveries Queue (Owner Review) -->
    @elseif ($tab === 'failed')
        @if ($deliveries->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">All Clear! No Failed Deliveries</h3>
                <p class="text-xs text-slate-500 mt-1">There are currently no deliveries requiring owner retry, rescheduling, or cancellation review.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($deliveries as $del)
                    <div class="bg-white rounded-2xl border-2 border-rose-100 shadow-sm p-5 hover:shadow-md transition">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                            <!-- Left: Failure Information & Order Details -->
                            <div class="space-y-3 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-extrabold text-slate-900">Order #{{ $del->order_id }}</span>
                                    <span class="text-xs text-slate-500">(Delivery #{{ $del->id }})</span>
                                    <x-status-badge status="Failed" />

                                    @if ($del->failure_resolution === 'pending_review')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Pending Owner Review
                                        </span>
                                    @elseif ($del->failure_resolution === 'rescheduled')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            Rescheduled
                                        </span>
                                    @elseif ($del->failure_resolution === 'cancelled')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                            Cancelled
                                        </span>
                                    @endif

                                    <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full">
                                        Retry Count: {{ $del->retry_count }}
                                    </span>
                                </div>

                                <!-- Customer & Delivery Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="font-bold text-slate-800">Customer:</span>
                                        <span class="text-slate-700 ml-1 font-semibold">{{ $del->customer_name }}</span>
                                        @if ($del->customer_phone)
                                            <a href="tel:{{ $del->customer_phone }}" class="text-blue-600 hover:underline ml-1">({{ $del->customer_phone }})</a>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800">Load:</span>
                                        <span class="text-blue-700 font-bold ml-1">{{ $del->breakdown }}</span>
                                    </div>
                                    <div class="md:col-span-2">
                                        <span class="font-bold text-slate-800">Address:</span>
                                        <span class="text-slate-600 ml-1">{{ $del->address }} ({{ $del->area ?? 'General' }})</span>
                                    </div>
                                </div>

                                <!-- Prominent Failure Reason & Notes Box -->
                                <div class="p-3.5 bg-rose-50/70 border border-rose-200 rounded-xl space-y-1">
                                    <div class="flex items-center justify-between gap-2 flex-wrap text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-extrabold text-rose-900">Reason:</span>
                                            <span class="font-bold text-rose-800 bg-rose-100/80 px-2 py-0.5 rounded-md">{{ $del->failure_reason ?: 'Unspecified' }}</span>
                                        </div>
                                        <div class="text-[11px] text-rose-700">
                                            Reported by: <strong>{{ $del->rider_name ?? 'Rider' }}</strong>
                                            @if ($del->failed_at)
                                                • {{ $del->failed_at->format('M d, Y — h:i A') }}
                                            @endif
                                        </div>
                                    </div>
                                    @if ($del->failure_notes)
                                        <p class="text-xs text-rose-800 italic mt-1 bg-white/70 p-2 rounded-lg border border-rose-100">
                                            "{{ $del->failure_notes }}"
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Owner Action Controls -->
                            <div class="flex flex-col sm:flex-row lg:flex-col gap-2 shrink-0 self-stretch sm:self-end lg:self-center" x-data>
                                @if (!$isRider)
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', 'retry-modal-{{ $del->id }}')"
                                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span>Retry Delivery</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', 'cancel-modal-{{ $del->id }}')"
                                        class="px-4 py-2.5 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Cancel Order</span>
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    @click="$dispatch('open-modal', 'attempts-modal-{{ $del->id }}')"
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>History ({{ $del->attempts->count() }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Owner Retry Modal -->
                        <x-modal name="retry-modal-{{ $del->id }}" title="Retry Delivery - Order #{{ $del->order_id }}" maxWidth="max-w-lg">
                            <form method="POST" action="{{ route('deliveries.retry', $del) }}" class="space-y-4">
                                @csrf

                                <div class="bg-blue-50/75 p-3.5 rounded-xl border border-blue-100 text-xs space-y-1">
                                    <p class="font-bold text-blue-900">{{ $del->customer_name }} • {{ $del->breakdown }}</p>
                                    <p class="text-blue-800 text-[11px] truncate">{{ $del->address }}</p>
                                    <p class="text-rose-800 text-[11px] font-semibold mt-1">
                                        Previous Failure: {{ $del->failure_reason }} @if ($del->failure_notes) ({{ $del->failure_notes }}) @endif
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Assign Rider *</label>
                                        <select name="rider_id" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
                                            @foreach ($riders as $r)
                                                <option value="{{ $r->id }}" {{ $del->rider_id == $r->id ? 'selected' : '' }}>
                                                    {{ $r->name }} ({{ $r->vehicle ?? 'Motorcycle' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">New Delivery Date *</label>
                                        <input
                                            type="date"
                                            name="delivery_date"
                                            required
                                            value="{{ now()->addDay()->toDateString() }}"
                                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Time Window</label>
                                        <input
                                            type="text"
                                            name="preferred_time"
                                            value="{{ old('preferred_time', $del->order?->preferred_time) }}"
                                            placeholder="e.g. 9:00 AM - 11:00 AM"
                                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Route Order</label>
                                        <input
                                            type="number"
                                            name="route_order"
                                            min="1"
                                            max="999"
                                            value="{{ $del->route_order ?? 99 }}"
                                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800"
                                        >
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" @click="$dispatch('close-modal', 'retry-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20">Confirm Retry & Dispatch</button>
                                </div>
                            </form>
                        </x-modal>

                        <!-- Owner Cancel Modal -->
                        <x-modal name="cancel-modal-{{ $del->id }}" title="Cancel Order #{{ $del->order_id }}" maxWidth="max-w-md">
                            <form method="POST" action="{{ route('deliveries.cancel', $del) }}" class="space-y-4">
                                @csrf

                                <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-800">
                                    <p class="font-bold">Are you sure you want to cancel this order?</p>
                                    <p class="mt-0.5 text-[11px]">This will mark Order #{{ $del->order_id }} as Cancelled and remove it from active dispatch queues.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Cancellation Reason *</label>
                                    <textarea
                                        name="reason"
                                        rows="3"
                                        required
                                        placeholder="Customer requested cancellation, unreachable after multiple attempts..."
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800"
                                    ></textarea>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" @click="$dispatch('close-modal', 'cancel-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Keep Order</button>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold">Confirm Cancellation</button>
                                </div>
                            </form>
                        </x-modal>

                        <!-- Attempts History Modal -->
                        <x-modal name="attempts-modal-{{ $del->id }}" title="Delivery Attempts History - Order #{{ $del->order_id }}" maxWidth="max-w-lg">
                            <div class="space-y-3">
                                @forelse ($del->attempts as $attempt)
                                    <div class="p-3.5 rounded-xl border {{ $attempt->status === 'Failed' ? 'bg-rose-50/50 border-rose-200' : ($attempt->status === 'Delivered' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200') }} text-xs space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-slate-900">Attempt #{{ $attempt->attempt_number }}</span>
                                            <x-status-badge :status="$attempt->status" />
                                        </div>
                                        <div class="text-slate-600 text-[11px]">
                                            Rider: <strong>{{ $attempt->rider_name ?? 'Unassigned' }}</strong>
                                        </div>
                                        <div class="text-slate-500 text-[11px] grid grid-cols-2 gap-1 pt-1 border-t border-slate-200/50">
                                            @if ($attempt->assigned_at)
                                                <span>Assigned: {{ $attempt->assigned_at->format('M d, h:i A') }}</span>
                                            @endif
                                            @if ($attempt->started_at)
                                                <span>Started: {{ $attempt->started_at->format('M d, h:i A') }}</span>
                                            @endif
                                            @if ($attempt->failed_at)
                                                <span class="text-rose-700 font-semibold">Failed: {{ $attempt->failed_at->format('M d, h:i A') }}</span>
                                            @endif
                                            @if ($attempt->delivered_at)
                                                <span class="text-emerald-700 font-semibold">Delivered: {{ $attempt->delivered_at->format('M d, h:i A') }}</span>
                                            @endif
                                        </div>
                                        @if ($attempt->failure_reason)
                                            <div class="mt-1 pt-1 text-[11px] text-rose-800">
                                                <strong>Reason:</strong> {{ $attempt->failure_reason }}
                                                @if ($attempt->failure_notes)
                                                    <span class="italic text-slate-600 block">"{{ $attempt->failure_notes }}"</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 text-center py-4">No logged attempts found for this delivery.</p>
                                @endforelse

                                <div class="pt-3 border-t border-slate-100 flex justify-end">
                                    <button type="button" @click="$dispatch('close-modal', 'attempts-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
                                </div>
                            </div>
                        </x-modal>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Delivered History / All Dispatches Table -->
    @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Delivery ID</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Load</th>
                            <th class="py-3.5 px-4">Rider</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4 sm:px-6">Proof & History</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($deliveries as $del)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 sm:px-6 font-bold text-slate-900">
                                    #{{ $del->id }}
                                    <div class="text-[10px] text-slate-400">Order #{{ $del->order_id }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $del->customer_name }}</div>
                                    <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $del->address }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">
                                    {{ $del->breakdown }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-medium text-slate-800">{{ $del->rider_name ?? 'Unassigned' }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <x-status-badge :status="$del->status" />
                                    @if ($del->retry_count > 0)
                                        <div class="text-[10px] text-amber-700 font-bold mt-0.5">({{ $del->retry_count }} retries)</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ $del->delivery_date ? $del->delivery_date->format('M d, Y') : $del->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="flex items-center gap-2 flex-wrap" x-data>
                                        @if ($del->proof_photo)
                                            <a href="{{ Storage::url($del->proof_photo) }}" target="_blank" class="px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold rounded-md text-[10px] inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Photo
                                            </a>
                                        @endif
                                        @if ($del->signature)
                                            <a href="{{ Storage::url($del->signature) }}" target="_blank" class="px-2 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold rounded-md text-[10px] inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                Signature
                                            </a>
                                        @endif

                                        @if ($del->attempts->isNotEmpty())
                                            <button
                                                type="button"
                                                @click="$dispatch('open-modal', 'attempts-modal-{{ $del->id }}')"
                                                class="px-2 py-1 bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold rounded-md text-[10px] inline-flex items-center gap-1 cursor-pointer"
                                            >
                                                History ({{ $del->attempts->count() }})
                                            </button>

                                            <!-- Attempts Modal for Table View -->
                                            <x-modal name="attempts-modal-{{ $del->id }}" title="Delivery Attempts History - Order #{{ $del->order_id }}" maxWidth="max-w-lg">
                                                <div class="space-y-3">
                                                    @foreach ($del->attempts as $attempt)
                                                        <div class="p-3.5 rounded-xl border {{ $attempt->status === 'Failed' ? 'bg-rose-50/50 border-rose-200' : ($attempt->status === 'Delivered' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200') }} text-xs space-y-1">
                                                            <div class="flex items-center justify-between">
                                                                <span class="font-extrabold text-slate-900">Attempt #{{ $attempt->attempt_number }}</span>
                                                                <x-status-badge :status="$attempt->status" />
                                                            </div>
                                                            <div class="text-slate-600 text-[11px]">
                                                                Rider: <strong>{{ $attempt->rider_name ?? 'Unassigned' }}</strong>
                                                            </div>
                                                            <div class="text-slate-500 text-[11px] grid grid-cols-2 gap-1 pt-1 border-t border-slate-200/50">
                                                                @if ($attempt->assigned_at)
                                                                    <span>Assigned: {{ $attempt->assigned_at->format('M d, h:i A') }}</span>
                                                                @endif
                                                                @if ($attempt->started_at)
                                                                    <span>Started: {{ $attempt->started_at->format('M d, h:i A') }}</span>
                                                                @endif
                                                                @if ($attempt->failed_at)
                                                                    <span class="text-rose-700 font-semibold">Failed: {{ $attempt->failed_at->format('M d, h:i A') }}</span>
                                                                @endif
                                                                @if ($attempt->delivered_at)
                                                                    <span class="text-emerald-700 font-semibold">Delivered: {{ $attempt->delivered_at->format('M d, h:i A') }}</span>
                                                                @endif
                                                            </div>
                                                            @if ($attempt->failure_reason)
                                                                <div class="mt-1 pt-1 text-[11px] text-rose-800">
                                                                    <strong>Reason:</strong> {{ $attempt->failure_reason }}
                                                                    @if ($attempt->failure_notes)
                                                                        <span class="italic text-slate-600 block">"{{ $attempt->failure_notes }}"</span>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach

                                                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                                                        <button type="button" @click="$dispatch('close-modal', 'attempts-modal-{{ $del->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
                                                    </div>
                                                </div>
                                            </x-modal>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $deliveries->links() }}
            </div>
        </div>
    @endif
</x-layouts.app>
