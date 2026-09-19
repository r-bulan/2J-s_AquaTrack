<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\MaintenanceLog;
use App\Services\ActivityLogService;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(
        protected MaintenanceService $maintenanceService,
        protected ActivityLogService $activityLogService
    ) {}

    public function index()
    {
        $equipment = MaintenanceLog::orderBy('next_service_date', 'asc')->get();

        $overdueCount = $equipment->filter->isOverdue()->count();
        $dueSoonCount = $equipment->filter->isDueSoon()->count();
        $goodCount = $equipment->count() - $overdueCount - $dueSoonCount;

        return view('maintenance.index', compact('equipment', 'overdueCount', 'dueSoonCount', 'goodCount'));
    }

    public function store(StoreMaintenanceRequest $request)
    {
        $log = MaintenanceLog::create($request->validated());

        $this->activityLogService->log(
            action: 'Maintenance Equipment Added',
            entityType: 'MaintenanceLog',
            entityId: $log->id,
            description: sprintf('Registered maintenance equipment: %s (%s)', $log->equipment_name, $log->equipment_type),
            newValues: $log->toArray()
        );

        return redirect()->route('maintenance.index')->with('success', "Equipment '{$log->equipment_name}' added to maintenance registry!");
    }

    public function markServiced(Request $request, MaintenanceLog $maintenance)
    {
        $notes = $request->input('notes');
        $this->maintenanceService->markServiced($maintenance, $notes);

        return back()->with('success', "Service recorded for {$maintenance->equipment_name}! Next service date updated.");
    }
}
