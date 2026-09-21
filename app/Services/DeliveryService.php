<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeliveryService
{
    public function __construct(
        protected RevenueService $revenueService,
        protected CustomerSyncService $customerSyncService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Create or update delivery record when an order is confirmed and assigned a rider.
     */
    public function assignRiderToOrder(Order $order, Rider $rider, int $routeOrder = 99): Delivery
    {
        return DB::transaction(function () use ($order, $rider, $routeOrder) {
            $order->update([
                'rider_id' => $rider->id,
                'rider_name' => $rider->name,
                'route_order' => $routeOrder,
                'status' => 'Confirmed',
            ]);

            $delivery = Delivery::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer?->phone,
                    'address' => $order->delivery_address ?: ($order->customer?->address ?? 'Station Pickup'),
                    'area' => $order->customer?->area,
                    'rider_id' => $rider->id,
                    'rider_name' => $rider->name,
                    'route_order' => $routeOrder,
                    'status' => 'Assigned',
                    'jug_count' => $order->jug_count,
                    'round_count' => $order->round_count,
                    'flat_count' => $order->flat_count,
                    'gallon_type' => $order->gallon_type,
                    'notes' => $order->notes,
                ]
            );

            // Record initial delivery attempt if none exists yet
            $existingAttempt = DeliveryAttempt::where('delivery_id', $delivery->id)
                ->orderBy('attempt_number', 'asc')
                ->first();

            if (!$existingAttempt) {
                DeliveryAttempt::create([
                    'delivery_id' => $delivery->id,
                    'rider_id' => $rider->id,
                    'rider_name' => $rider->name,
                    'attempt_number' => 1,
                    'status' => 'Assigned',
                    'assigned_at' => now(),
                ]);
            } else {
                // If still pending initial start, update rider details on attempt #1
                if ($existingAttempt->status === 'Assigned') {
                    $existingAttempt->update([
                        'rider_id' => $rider->id,
                        'rider_name' => $rider->name,
                        'assigned_at' => now(),
                    ]);
                }
            }

            $this->activityLogService->log(
                action: 'Rider Assigned',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf('Assigned Rider %s to Order #%d (Route #%d)', $rider->name, $order->id, $routeOrder)
            );

            return $delivery;
        });
    }

    /**
     * Rider starts delivery: Assigned -> En Route
     */
    public function startDelivery(Delivery $delivery): Delivery
    {
        return DB::transaction(function () use ($delivery) {
            $lockedDelivery = Delivery::lockForUpdate()->with('order')->findOrFail($delivery->id);

            if ($lockedDelivery->status !== 'Assigned') {
                throw new \DomainException("Cannot start delivery in '{$lockedDelivery->status}' status.");
            }

            $lockedDelivery->update(['status' => 'En Route']);
            $lockedDelivery->order->update(['status' => 'Out for Delivery']);

            // Update current attempt status to En Route
            $attempt = DeliveryAttempt::where('delivery_id', $lockedDelivery->id)
                ->orderBy('attempt_number', 'desc')
                ->first();

            if ($attempt && $attempt->status === 'Assigned') {
                $attempt->update([
                    'status' => 'En Route',
                    'started_at' => now(),
                ]);
            }

            $this->activityLogService->log(
                action: 'Delivery En Route',
                entityType: 'Delivery',
                entityId: $lockedDelivery->id,
                description: sprintf('Delivery #%d for Order #%d is now En Route', $lockedDelivery->id, $lockedDelivery->order_id)
            );

            return $lockedDelivery;
        });
    }

    /**
     * Complete delivery: En Route -> Delivered with Proof of Delivery
     */
    public function completeDelivery(
        Delivery $delivery,
        ?UploadedFile $proofPhoto = null,
        ?string $signatureBase64 = null,
        ?string $notes = null,
        ?int $returnedJugs = null
    ): Delivery {
        return DB::transaction(function () use ($delivery, $proofPhoto, $signatureBase64, $notes, $returnedJugs) {
            $lockedDelivery = Delivery::lockForUpdate()->with('order')->findOrFail($delivery->id);

            if ($lockedDelivery->status !== 'En Route') {
                throw new \DomainException("Cannot complete delivery in '{$lockedDelivery->status}' status. Delivery must be En Route first.");
            }

            $photoPath = $lockedDelivery->proof_photo;
            if ($proofPhoto) {
                $filename = 'pod_' . $lockedDelivery->id . '_' . Str::random(10) . '.' . $proofPhoto->getClientOriginalExtension();
                $photoPath = $proofPhoto->storeAs('proofs', $filename, 'public');
            }

            $signaturePath = $lockedDelivery->signature;
            if ($signatureBase64 && str_starts_with($signatureBase64, 'data:image')) {
                $data = explode(',', $signatureBase64);
                $encoded = end($data);
                $decoded = base64_decode($encoded);
                if ($decoded !== false) {
                    $sigFilename = 'sig_' . $lockedDelivery->id . '_' . Str::random(10) . '.png';
                    Storage::disk('public')->put('signatures/' . $sigFilename, $decoded);
                    $signaturePath = 'signatures/' . $sigFilename;
                }
            }

            $returnedCount = max(0, (int) ($returnedJugs ?? $lockedDelivery->returned_jugs ?? 0));

            $lockedDelivery->update([
                'status' => 'Delivered',
                'failure_resolution' => Delivery::RESOLUTION_RESOLVED,
                'delivery_date' => now()->toDateString(),
                'proof_photo' => $photoPath,
                'signature' => $signaturePath,
                'returned_jugs' => $returnedCount,
                'notes' => $notes ?: $lockedDelivery->notes,
            ]);

            // Update latest attempt record to Delivered
            $attempt = DeliveryAttempt::where('delivery_id', $lockedDelivery->id)
                ->orderBy('attempt_number', 'desc')
                ->first();

            if ($attempt && $attempt->status === 'En Route') {
                $attempt->update([
                    'status' => 'Delivered',
                    'delivered_at' => now(),
                ]);
            } elseif (!$attempt) {
                DeliveryAttempt::create([
                    'delivery_id' => $lockedDelivery->id,
                    'rider_id' => $lockedDelivery->rider_id,
                    'rider_name' => $lockedDelivery->rider_name,
                    'attempt_number' => 1,
                    'status' => 'Delivered',
                    'delivered_at' => now(),
                ]);
            }

            // Synchronize parent order
            $order = $lockedDelivery->order;
            $order->update([
                'status' => 'Delivered',
                'payment_status' => (strcasecmp($order->payment_method, 'Credit') === 0) ? 'Credit' : 'Paid',
            ]);

            // Synchronize revenue idempotently
            $this->revenueService->recordOrderDeliveredIncome($order);

            // Synchronize customer loyalty, ledger, last order date
            $this->customerSyncService->syncDeliveredOrder($order);

            $this->activityLogService->log(
                action: 'Delivery Completed',
                entityType: 'Delivery',
                entityId: $lockedDelivery->id,
                description: sprintf('Delivery #%d (Order #%d) marked Delivered by Rider %s (Returned Jugs: %d)', $lockedDelivery->id, $order->id, $lockedDelivery->rider_name, $returnedCount)
            );

            return $lockedDelivery;
        });
    }

    /**
     * Mark delivery as failed.
     */
    public function failDelivery(Delivery $delivery, string $reason, ?string $notes = null, ?User $user = null): Delivery
    {
        return DB::transaction(function () use ($delivery, $reason, $notes, $user) {
            $lockedDelivery = Delivery::lockForUpdate()->with('order')->findOrFail($delivery->id);

            if (!in_array($lockedDelivery->status, ['Assigned', 'En Route'])) {
                throw new \DomainException("Cannot mark delivery as Failed in '{$lockedDelivery->status}' status.");
            }

            $userId = $user?->id ?? Auth::id();
            $actorName = $user?->name ?? (Auth::user()?->name ?? 'System');

            $lockedDelivery->update([
                'status' => 'Failed',
                'failure_reason' => $reason,
                'failure_notes' => $notes,
                'failed_at' => now(),
                'failed_by_user_id' => $userId,
                'failure_resolution' => Delivery::RESOLUTION_PENDING_REVIEW,
            ]);

            // Parent order moves to Delivery Failed status
            $lockedDelivery->order->update([
                'status' => 'Delivery Failed',
            ]);

            // Update existing active attempt or create a new attempt
            $attempt = DeliveryAttempt::where('delivery_id', $lockedDelivery->id)
                ->orderBy('attempt_number', 'desc')
                ->first();

            if ($attempt && in_array($attempt->status, ['Assigned', 'En Route'])) {
                $attempt->update([
                    'status' => 'Failed',
                    'failure_reason' => $reason,
                    'failure_notes' => $notes,
                    'failed_at' => now(),
                ]);
            } else {
                $latestAttemptNum = DeliveryAttempt::where('delivery_id', $lockedDelivery->id)->max('attempt_number') ?? 0;
                DeliveryAttempt::create([
                    'delivery_id' => $lockedDelivery->id,
                    'rider_id' => $lockedDelivery->rider_id,
                    'rider_name' => $lockedDelivery->rider_name,
                    'attempt_number' => $latestAttemptNum + 1,
                    'status' => 'Failed',
                    'failure_reason' => $reason,
                    'failure_notes' => $notes,
                    'failed_at' => now(),
                ]);
            }

            $this->activityLogService->log(
                action: 'Delivery Failed',
                entityType: 'Delivery',
                entityId: $lockedDelivery->id,
                description: sprintf(
                    'Delivery #%d marked Failed by %s. Reason: %s%s',
                    $lockedDelivery->id,
                    $actorName,
                    $reason,
                    $notes ? " ({$notes})" : ''
                )
            );

            return $lockedDelivery;
        });
    }

    /**
     * Owner retries/reschedules a failed delivery.
     */
    public function retryDelivery(
        Delivery $delivery,
        Rider $rider,
        string $newDate,
        ?string $preferredTime = null,
        ?int $routeOrder = 99,
        ?User $user = null
    ): Delivery {
        return DB::transaction(function () use ($delivery, $rider, $newDate, $preferredTime, $routeOrder, $user) {
            $lockedDelivery = Delivery::lockForUpdate()->with('order')->findOrFail($delivery->id);

            if ($lockedDelivery->status !== 'Failed') {
                throw new \DomainException("Cannot retry delivery in '{$lockedDelivery->status}' status. Only Failed deliveries can be retried.");
            }

            $userId = $user?->id ?? Auth::id();
            $actorName = $user?->name ?? (Auth::user()?->name ?? 'Admin');

            $newRetryCount = $lockedDelivery->retry_count + 1;
            $latestAttemptNum = DeliveryAttempt::where('delivery_id', $lockedDelivery->id)->max('attempt_number') ?? 0;
            $nextAttemptNum = $latestAttemptNum + 1;

            $lockedDelivery->update([
                'status' => 'Assigned',
                'rider_id' => $rider->id,
                'rider_name' => $rider->name,
                'delivery_date' => $newDate,
                'route_order' => $routeOrder ?? 99,
                'retry_count' => $newRetryCount,
                'failure_resolution' => Delivery::RESOLUTION_RESCHEDULED,
                'resolved_at' => now(),
                'resolved_by_user_id' => $userId,
            ]);

            // Parent order returns to Confirmed status
            $orderUpdates = [
                'status' => 'Confirmed',
                'rider_id' => $rider->id,
                'rider_name' => $rider->name,
                'route_order' => $routeOrder ?? 99,
            ];
            if ($preferredTime) {
                $orderUpdates['preferred_time'] = $preferredTime;
            }
            $lockedDelivery->order->update($orderUpdates);

            // Create new DeliveryAttempt for the retry
            DeliveryAttempt::create([
                'delivery_id' => $lockedDelivery->id,
                'rider_id' => $rider->id,
                'rider_name' => $rider->name,
                'attempt_number' => $nextAttemptNum,
                'status' => 'Assigned',
                'assigned_at' => now(),
            ]);

            $this->activityLogService->log(
                action: 'Delivery Retried',
                entityType: 'Delivery',
                entityId: $lockedDelivery->id,
                description: sprintf(
                    '%s retried Delivery #%d (Order #%d). Assigned to Rider %s for %s (Attempt #%d)',
                    $actorName,
                    $lockedDelivery->id,
                    $lockedDelivery->order_id,
                    $rider->name,
                    $newDate,
                    $nextAttemptNum
                )
            );

            return $lockedDelivery;
        });
    }

    /**
     * Owner cancels order after failed delivery.
     */
    public function cancelFailedDelivery(Delivery $delivery, string $reason, ?User $user = null): Delivery
    {
        return DB::transaction(function () use ($delivery, $reason, $user) {
            $lockedDelivery = Delivery::lockForUpdate()->with('order')->findOrFail($delivery->id);

            if ($lockedDelivery->status !== 'Failed') {
                throw new \DomainException("Cannot cancel delivery in '{$lockedDelivery->status}' status through the failed-delivery review workflow.");
            }

            $userId = $user?->id ?? Auth::id();
            $actorName = $user?->name ?? (Auth::user()?->name ?? 'Admin');

            $lockedDelivery->update([
                'status' => 'Failed',
                'failure_resolution' => Delivery::RESOLUTION_CANCELLED,
                'resolved_at' => now(),
                'resolved_by_user_id' => $userId,
            ]);

            // Parent order marked Cancelled
            $order = $lockedDelivery->order;
            $order->update([
                'status' => 'Cancelled',
                'notes' => $order->notes ? ($order->notes . " | Cancelled after failed delivery: " . $reason) : "Cancelled after failed delivery: " . $reason,
            ]);

            $this->activityLogService->log(
                action: 'Order Cancelled After Failed Delivery',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf(
                    'Order #%d (Delivery #%d) cancelled by %s after failed delivery. Reason: %s',
                    $order->id,
                    $lockedDelivery->id,
                    $actorName,
                    $reason
                )
            );

            return $lockedDelivery;
        });
    }
}
