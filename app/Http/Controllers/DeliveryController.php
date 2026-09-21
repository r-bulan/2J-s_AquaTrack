<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProofOfDeliveryRequest;
use App\Models\Delivery;
use App\Models\Rider;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

        $tab = $request->query('tab', 'queue'); // queue, failed, delivered, all
        $riderFilter = $request->query('rider_id');

        $query = Delivery::with(['order', 'rider', 'attempts.rider', 'failedByUser', 'resolvedByUser']);

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
        } elseif ($tab === 'failed') {
            $query->where('status', 'Failed')
                ->orderBy('failed_at', 'desc')
                ->orderBy('id', 'desc');
        } elseif ($tab === 'delivered') {
            $query->where('status', 'Delivered')
                ->orderBy('delivery_date', 'desc')
                ->orderBy('id', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $deliveries = $query->paginate(15)->withQueryString();
        $riders = $user->isAdmin() ? Rider::where('status', 'Active')->orderBy('name')->get() : collect();

        $baseCountQuery = Delivery::query();
        if ($isRider) {
            $baseCountQuery->where('rider_id', $currentRider?->id);
        } elseif ($riderFilter) {
            $baseCountQuery->where('rider_id', $riderFilter);
        }

        $queueCount = (clone $baseCountQuery)->whereIn('status', ['Assigned', 'En Route'])->count();
        $failedCount = (clone $baseCountQuery)->where('status', 'Failed')->count();

        return view('deliveries.index', compact('deliveries', 'tab', 'isRider', 'currentRider', 'riders', 'queueCount', 'failedCount'));
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
                $request->input('notes'),
                $request->filled('returned_jugs') ? (int) $request->input('returned_jugs') : null
            );

            return back()->with('success', "Delivery #{$delivery->id} marked Delivered! Revenue and records synchronized.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function fail(Request $request, Delivery $delivery)
    {
        $this->authorize('reportFailure', $delivery);

        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(Delivery::FAILURE_REASONS)],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:reason,Other'],
        ], [
            'reason.required' => 'Please select a reason for the failed delivery.',
            'reason.in' => 'The selected failure reason is invalid.',
            'notes.required_if' => 'Please provide detailed notes when selecting "Other" as the failure reason.',
        ]);

        try {
            $this->deliveryService->failDelivery($delivery, $validated['reason'], $validated['notes'] ?? null, Auth::user());
            return back()->with('warning', "Delivery #{$delivery->id} marked as Failed. Reason: {$validated['reason']}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function retry(Request $request, Delivery $delivery)
    {
        $this->authorize('retry', $delivery);

        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
            'delivery_date' => 'required|date',
            'preferred_time' => 'nullable|string|max:100',
            'route_order' => 'nullable|integer|min:1|max:999',
        ]);

        $rider = Rider::findOrFail($validated['rider_id']);

        try {
            $this->deliveryService->retryDelivery(
                $delivery,
                $rider,
                $validated['delivery_date'],
                $validated['preferred_time'] ?? null,
                isset($validated['route_order']) ? (int) $validated['route_order'] : 99,
                Auth::user()
            );

            return back()->with('success', "Delivery #{$delivery->id} retried and assigned to Rider {$rider->name}!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, Delivery $delivery)
    {
        $this->authorize('cancelFailed', $delivery);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Please provide a reason for cancelling this order.',
        ]);

        try {
            $this->deliveryService->cancelFailedDelivery($delivery, $validated['reason'], Auth::user());
            return back()->with('success', "Order #{$delivery->order_id} (Delivery #{$delivery->id}) has been cancelled.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
