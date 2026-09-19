<x-layouts.app title="Customer Feedback">
    <x-page-header title="Customer Feedback & Complaints" subtitle="Customer reviews, service ratings, and complaint resolution tracking" />

    <!-- Stat Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Average Rating</span>
            <div class="flex items-center gap-1.5 mt-1">
                <span class="text-xl font-extrabold text-slate-800">{{ $averageRating }}</span>
                <span class="text-amber-400 text-sm">★</span>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-amber-600 block">Open Inquiries</span>
            <span class="text-xl font-extrabold text-amber-700 mt-1 block">{{ $openCount }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-blue-600 block">In Progress</span>
            <span class="text-xl font-extrabold text-blue-700 mt-1 block">{{ $inProgressCount }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-emerald-600 block">Resolved</span>
            <span class="text-xl font-extrabold text-emerald-700 mt-1 block">{{ $resolvedCount }}</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <div class="flex items-center gap-2 overflow-x-auto">
            <a
                href="{{ route('feedback.index') }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ empty($status) && empty($type) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                All Submissions
            </a>
            <a
                href="{{ route('feedback.index', ['status' => 'Open']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $status === 'Open' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                Open Only
            </a>
            <a
                href="{{ route('feedback.index', ['type' => 'Complaint']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $type === 'Complaint' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                Complaints Only
            </a>
            <a
                href="{{ route('feedback.index', ['status' => 'Resolved']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $status === 'Resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                Resolved
            </a>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="space-y-4">
        @if ($feedback->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <x-empty-state message="No feedback submissions found." />
            </div>
        @else
            @foreach ($feedback as $fb)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition" x-data>
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2.5 flex-wrap mb-1">
                                <h3 class="text-sm font-bold text-slate-900">{{ $fb->customer_name }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $fb->type === 'Complaint' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $fb->type }}
                                </span>
                                <x-status-badge :status="$fb->status" />
                                <x-star-rating :rating="$fb->rating" />
                            </div>

                            <p class="text-xs text-slate-500 mb-2">
                                Submitted on {{ $fb->created_at->format('M d, Y h:i A') }}
                                @if ($fb->customer?->phone)
                                    • <a href="tel:{{ $fb->customer->phone }}" class="text-blue-600 hover:underline">{{ $fb->customer->phone }}</a>
                                @endif
                            </p>

                            <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed">
                                "{{ $fb->message }}"
                            </p>

                            @if ($fb->admin_response)
                                <div class="mt-3 pl-3 border-l-2 border-blue-500 text-xs">
                                    <p class="font-bold text-blue-900">Station Response:</p>
                                    <p class="text-slate-600 mt-0.5">{{ $fb->admin_response }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="shrink-0 self-end sm:self-start">
                            <button
                                type="button"
                                @click="$dispatch('open-modal', 'respond-modal-{{ $fb->id }}')"
                                class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Update / Reply</span>
                            </button>
                        </div>
                    </div>

                    <!-- Response Modal -->
                    <x-modal name="respond-modal-{{ $fb->id }}" title="Update Feedback #{{ $fb->id }}">
                        <form method="POST" action="{{ route('feedback.status', $fb) }}" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status *</label>
                                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                                    <option value="Open" {{ $fb->status === 'Open' ? 'selected' : '' }}>Open</option>
                                    <option value="In Progress" {{ $fb->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Resolved" {{ $fb->status === 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Station Official Reply</label>
                                <textarea name="admin_response" rows="3" placeholder="Write response to customer..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">{{ $fb->admin_response }}</textarea>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                <button type="button" @click="$dispatch('close-modal', 'respond-modal-{{ $fb->id }}')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Save Status</button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $feedback->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
