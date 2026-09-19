<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
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
        return view('settings.index', compact('prices'));
    }

    public function update(StoreSettingRequest $request)
    {
        $validated = $request->validated();

        $this->pricingService->updatePrices(
            (float) $validated['round_gallon_price'],
            (float) $validated['flat_gallon_price'],
            $this->activityLogService
        );

        return back()->with('success', 'Gallon pricing settings successfully updated!');
    }
}
