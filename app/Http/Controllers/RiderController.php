<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRiderRequest;
use App\Http\Requests\UpdateRiderRequest;
use App\Models\Rider;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RiderController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index()
    {
        $riders = Rider::with('user')->withCount('deliveries')->orderBy('name')->get();

        $activeRidersCount = $riders->where('status', 'Active')->count();
        $totalCashAdvance = $riders->sum('cash_advance');

        return view('riders.index', compact('riders', 'activeRidersCount', 'totalCashAdvance'));
    }

    public function store(StoreRiderRequest $request)
    {
        $validated = $request->validated();

        $rider = DB::transaction(function () use ($validated) {
            // Admin/Owner-created Rider accounts are deliberately pre-verified
            // because they are provisioned directly by station management.
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'rider',
                'email_verified_at' => now(),
            ]);

            return Rider::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
                'wage_rate' => $validated['wage_rate'],
                'cash_advance' => $validated['cash_advance'] ?? 0,
                'area' => $validated['area'] ?? null,
                'vehicle' => $validated['vehicle'] ?? null,
            ]);
        });

        $this->activityLogService->log(
            action: 'Rider Created',
            entityType: 'Rider',
            entityId: $rider->id,
            description: sprintf('Registered new Rider %s (Vehicle: %s, Wage: ₱%.2f)', $rider->name, $rider->vehicle ?? 'None', $rider->wage_rate),
            newValues: $rider->toArray()
        );

        return redirect()->route('riders.index')->with('success', "Rider {$rider->name} added successfully!");
    }

    public function update(UpdateRiderRequest $request, Rider $rider)
    {
        $old = $rider->toArray();
        $rider->update($request->validated());

        $this->activityLogService->log(
            action: 'Rider Updated',
            entityType: 'Rider',
            entityId: $rider->id,
            description: sprintf('Updated Rider %s information and payroll settings', $rider->name),
            oldValues: $old,
            newValues: $rider->toArray()
        );

        return back()->with('success', "Rider {$rider->name} updated successfully!");
    }

    public function toggleStatus(Rider $rider)
    {
        $newStatus = $rider->status === 'Active' ? 'Inactive' : 'Active';
        $rider->update(['status' => $newStatus]);

        $this->activityLogService->log(
            action: 'Rider Status Changed',
            entityType: 'Rider',
            entityId: $rider->id,
            description: sprintf('Rider %s status changed to %s', $rider->name, $newStatus)
        );

        return back()->with('success', "Rider {$rider->name} is now {$newStatus}!");
    }

    public function adjustCashAdvance(Request $request, Rider $rider)
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,deduct',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $oldAdvance = $rider->cash_advance;

        $newAdvance = $validated['adjustment_type'] === 'add'
            ? $oldAdvance + $amount
            : max(0, $oldAdvance - $amount);

        $rider->update(['cash_advance' => $newAdvance]);

        $this->activityLogService->log(
            action: 'Cash Advance Adjusted',
            entityType: 'Rider',
            entityId: $rider->id,
            description: sprintf(
                '%s ₱%.2f cash advance for Rider %s. New balance: ₱%.2f (%s)',
                $validated['adjustment_type'] === 'add' ? 'Added' : 'Deducted',
                $amount,
                $rider->name,
                $newAdvance,
                $validated['notes'] ?? 'No notes'
            ),
            oldValues: ['cash_advance' => $oldAdvance],
            newValues: ['cash_advance' => $newAdvance]
        );

        return back()->with('success', "Cash advance balance updated for {$rider->name}!");
    }
}
