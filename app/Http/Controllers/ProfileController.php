<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNameRequest;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Update the authenticated user's self name.
     */
    public function updateName(UpdateNameRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $newName = $validated['name'];
        $oldName = $user->name;

        if ($newName === $oldName) {
            return back()->with('info', 'Your name is already set to that value.');
        }

        DB::transaction(function () use ($user, $newName) {
            // Update the user record only
            $user->update(['name' => $newName]);

            // Synchronize current profile entity (Customer / Rider) without modifying historical snapshots
            if ($user->isCustomer() && $user->customer) {
                $user->customer->update(['name' => $newName]);

                if ($user->customer->jugLedger) {
                    $user->customer->jugLedger->update(['customer_name' => $newName]);
                }
                if ($user->customer->creditLedger) {
                    $user->customer->creditLedger->update(['customer_name' => $newName]);
                }
                if ($user->customer->loyaltyRecord) {
                    $user->customer->loyaltyRecord->update(['customer_name' => $newName]);
                }
            } elseif ($user->isRider() && $user->rider) {
                $user->rider->update(['name' => $newName]);
            }
        });

        $this->activityLogService->log(
            action: 'Profile Name Updated',
            entityType: 'User',
            entityId: $user->id,
            description: sprintf(
                'User %s (%s) updated their self display name from "%s" to "%s"',
                $user->email,
                $user->role,
                $oldName,
                $newName
            ),
            oldValues: ['name' => $oldName],
            newValues: ['name' => $newName]
        );

        return back()->with('success', "Your name has been updated to {$newName}!");
    }
}
