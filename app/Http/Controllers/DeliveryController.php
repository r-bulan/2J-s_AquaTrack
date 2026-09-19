<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProofOfDeliveryRequest;
use App\Models\Delivery;
use App\Models\Rider;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $isRider = $user->isRider();
        $currentRider = $user->rider;

        $tab = $request->query('tab', 'queue'); // queue, all, delivered
        $riderFilter = $request->query('rider_id');

        $query = Delivery::with(['order', 'rider']);

        // Strict role authorization: Rider can only view their own deliveries
        if ($isRider) {
            if (!$currentRider) {
                abort(403, 'No linked rider profile found for this account.');
            }
            $query->where('rider_id', $currentRider->id);
        } elseif ($riderFilter) {
            $query->where('rider_id', $riderFilter);
        }

        if ($tab === 'queue') {
            // Route Queue excludes Delivered and Failed
            $query->whereIn('status', ['Assigned', 'En Route'])
                ->orderBy('route_order', 'asc')
                ->orderBy('id', 'asc');
        } elseif ($tab === 'delivered') {
            $query->where('status', 'Delivered')
                ->orderBy('delivery_date', 'desc')
                ->orderBy('id', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $deliveries = $query->paginate(15)->withQueryString();
        $riders = $user->isAdmin() ? Rider::where('status', 'Active')->get() : collect();

        $queueCount = (clone $query)->whereIn('status', ['Assigned', 'En Route'])->count();

        return view('deliveries.index', compact('deliveries', 'tab', 'isRider', 'currentRider', 'riders', 'queueCount'));
    }

    public function start(Delivery $delivery)
    {
        $this->authorize('update', $delivery);

        try {
            $this->deliveryService->startDelivery($delivery);
            return back()->with('success', "Delivery #{$delivery->id} is now En Route!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function complete(ProofOfDeliveryRequest $request, Delivery $delivery)
    {
        $this->authorize('update', $delivery);

        try {
            $this->deliveryService->completeDelivery(
                $delivery,
                $request->file('proof_photo'),
                $request->input('signature'),
                $request->input('notes')
            );

            return back()->with('success', "Delivery #{$delivery->id} marked Delivered! Revenue and records synchronized.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function fail(Request $request, Delivery $delivery)
    {
        $this->authorize('update', $delivery);

        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->deliveryService->failDelivery($delivery, $request->input('reason'));
            return back()->with('warning', "Delivery #{$delivery->id} marked as Failed.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
