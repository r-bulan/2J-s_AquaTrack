<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Http\UploadedFile;
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
        if ($delivery->status !== 'Assigned') {
            throw new \DomainException("Cannot start delivery in '{$delivery->status}' status.");
        }

        return DB::transaction(function () use ($delivery) {
            $delivery->update(['status' => 'En Route']);
            $delivery->order->update(['status' => 'Out for Delivery']);

            $this->activityLogService->log(
                action: 'Delivery En Route',
                entityType: 'Delivery',
                entityId: $delivery->id,
                description: sprintf('Delivery #%d for Order #%d is now En Route', $delivery->id, $delivery->order_id)
            );

            return $delivery;
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
        if ($delivery->status !== 'En Route') {
            throw new \DomainException("Cannot complete delivery in '{$delivery->status}' status. Delivery must be En Route first.");
        }

        return DB::transaction(function () use ($delivery, $proofPhoto, $signatureBase64, $notes, $returnedJugs) {
            $photoPath = $delivery->proof_photo;
            if ($proofPhoto) {
                $filename = 'pod_' . $delivery->id . '_' . Str::random(10) . '.' . $proofPhoto->getClientOriginalExtension();
                $photoPath = $proofPhoto->storeAs('proofs', $filename, 'public');
            }

            $signaturePath = $delivery->signature;
            if ($signatureBase64 && str_starts_with($signatureBase64, 'data:image')) {
                // Decode base64 signature and store safely on disk
                $data = explode(',', $signatureBase64);
                $encoded = end($data);
                $decoded = base64_decode($encoded);
                if ($decoded !== false) {
                    $sigFilename = 'sig_' . $delivery->id . '_' . Str::random(10) . '.png';
                    Storage::disk('public')->put('signatures/' . $sigFilename, $decoded);
                    $signaturePath = 'signatures/' . $sigFilename;
                }
            }

            $returnedCount = max(0, (int) ($returnedJugs ?? $delivery->returned_jugs ?? 0));

            $delivery->update([
                'status' => 'Delivered',
                'delivery_date' => now()->toDateString(),
                'proof_photo' => $photoPath,
                'signature' => $signaturePath,
                'returned_jugs' => $returnedCount,
                'notes' => $notes ?: $delivery->notes,
            ]);

            // Synchronize parent order
            $order = $delivery->order;
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
                entityId: $delivery->id,
                description: sprintf('Delivery #%d (Order #%d) marked Delivered by Rider %s (Returned Jugs: %d)', $delivery->id, $order->id, $delivery->rider_name, $returnedCount)
            );

            return $delivery;
        });
    }

    /**
     * Mark delivery as failed.
     */
    public function failDelivery(Delivery $delivery, ?string $reason = null): Delivery
    {
        return DB::transaction(function () use ($delivery, $reason) {
            $delivery->update([
                'status' => 'Failed',
                'notes' => $reason ? ($delivery->notes . " | Failed: " . $reason) : $delivery->notes,
            ]);

            $this->activityLogService->log(
                action: 'Delivery Failed',
                entityType: 'Delivery',
                entityId: $delivery->id,
                description: sprintf('Delivery #%d marked Failed. Reason: %s', $delivery->id, $reason ?: 'Unspecified')
            );

            return $delivery;
        });
    }
}
