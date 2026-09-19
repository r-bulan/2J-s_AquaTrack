<?php

namespace App\Services;

use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function markServiced(MaintenanceLog $log, ?string $notes = null): MaintenanceLog
    {
        return DB::transaction(function () use ($log, $notes) {
            $today = now()->startOfDay();
            $interval = $log->service_interval_days ?: 90;
            $nextDate = $today->copy()->addDays($interval);

            $oldValues = $log->toArray();

            $log->update([
                'last_service_date' => $today->toDateString(),
                'next_service_date' => $nextDate->toDateString(),
                'notes' => $notes ? ($log->notes . "\n" . now()->format('Y-m-d') . ": " . $notes) : $log->notes,
            ]);

            $this->activityLogService->log(
                action: 'Equipment Serviced',
                entityType: 'MaintenanceLog',
                entityId: $log->id,
                description: sprintf('Equipment %s serviced. Next service scheduled for %s', $log->equipment_name, $nextDate->toDateString()),
                oldValues: $oldValues,
                newValues: $log->toArray()
            );

            return $log;
        });
    }
}
