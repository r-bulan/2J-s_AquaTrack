<x-layouts.app title="Activity & Audit Logs">
    <x-page-header title="Activity & Audit Logs" subtitle="Tamper-evident administrative audit trail of station actions and system modifications" />

    <!-- Search & Filter Controls -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Query -->
            <div class="lg:col-span-2 relative">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search logs description or actor..."
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Action Filter -->
            <div>
                <select name="action" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700">
                    <option value="">-- All Actions --</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" {{ $action === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Module Filter -->
            <div>
                <select name="module" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700">
                    <option value="">-- All Modules --</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod }}" {{ $module === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Filter -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition">
                    Filter Logs
                </button>
                @if ($search || $action || $module || $userId || $date)
                    <a href="{{ route('activity-logs.index') }}" class="py-2 px-3 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 text-xs text-center font-medium">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($logs->isEmpty())
            <x-empty-state message="No activity logs found matching the filter criteria." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Timestamp</th>
                            <th class="py-3.5 px-4">Actor / Role</th>
                            <th class="py-3.5 px-4">Action</th>
                            <th class="py-3.5 px-4">Module</th>
                            <th class="py-3.5 px-4">Description</th>
                            <th class="py-3.5 px-4">IP Address</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 sm:px-6 text-slate-500 whitespace-nowrap">
                                    {{ $log->created_at->format('M d, Y h:i:s A') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $log->user_name }}</div>
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-semibold uppercase tracking-wider {{ $log->user_role === 'admin' ? 'bg-blue-100 text-blue-800' : ($log->user_role === 'rider' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $log->user_role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-900 bg-slate-100 px-2 py-1 rounded-md">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 font-medium">
                                    {{ $log->entity_type ?: '-' }}
                                    @if ($log->entity_id)
                                        #{{ $log->entity_id }}
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 max-w-md break-words font-medium">
                                    {{ $log->description }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 font-mono text-[11px]">
                                    {{ $log->ip_address ?: '127.0.0.1' }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right" x-data>
                                    @if ($log->old_values || $log->new_values)
                                        <button
                                            type="button"
                                            @click="$dispatch('open-modal', 'diff-modal-{{ $log->id }}')"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition"
                                        >
                                            View Diffs
                                        </button>

                                        <!-- Diffs Modal -->
                                        <x-modal name="diff-modal-{{ $log->id }}" title="Log Details #{{ $log->id }}">
                                            <div class="space-y-3 text-xs">
                                                <div class="p-3 bg-slate-50 rounded-xl">
                                                    <p><strong>Action:</strong> {{ $log->action }}</p>
                                                    <p class="mt-1"><strong>Description:</strong> {{ $log->description }}</p>
                                                    <p class="mt-1 text-slate-400 text-[10px]">User Agent: {{ $log->user_agent }}</p>
                                                </div>

                                                @if ($log->old_values)
                                                    <div>
                                                        <span class="font-bold text-rose-700 block mb-1">Previous Values:</span>
                                                        <pre class="bg-rose-50 p-2.5 rounded-xl border border-rose-100 text-[11px] overflow-x-auto text-rose-900">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif

                                                @if ($log->new_values)
                                                    <div>
                                                        <span class="font-bold text-emerald-700 block mb-1">New Values:</span>
                                                        <pre class="bg-emerald-50 p-2.5 rounded-xl border border-emerald-100 text-[11px] overflow-x-auto text-emerald-900">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </x-modal>
                                    @else
                                        <span class="text-slate-300 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
