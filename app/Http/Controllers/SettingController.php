<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected ActivityLogService $activityLogService
    ) {}

    public function index()
    {
        $prices = $this->pricingService->getPrices();
        $loyaltyRefillsNeeded = (int) Setting::get('loyalty_refills_needed', 10);

        return view('settings.index', compact('prices', 'loyaltyRefillsNeeded'));
    }

    public function update(StoreSettingRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['round_gallon_price']) && isset($validated['flat_gallon_price'])) {
            $this->pricingService->updatePrices(
                (float) $validated['round_gallon_price'],
                (float) $validated['flat_gallon_price'],
                $this->activityLogService
            );
        }

        if (isset($validated['loyalty_refills_needed'])) {
            $oldThreshold = Setting::get('loyalty_refills_needed', 10);
            $newThreshold = (int) $validated['loyalty_refills_needed'];

            Setting::set('loyalty_refills_needed', $newThreshold);

            $this->activityLogService->log(
                action: 'Settings Updated',
                entityType: 'Setting',
                entityId: null,
                description: sprintf('Updated loyalty refill threshold from %s to %d refills per free refill', $oldThreshold, $newThreshold),
                oldValues: ['loyalty_refills_needed' => $oldThreshold],
                newValues: ['loyalty_refills_needed' => $newThreshold]
            );
        }

        return back()->with('success', 'Station settings successfully updated!');
    }
}
