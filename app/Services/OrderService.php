<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected ActivityLogService $activityLogService
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);

            $hasExplicitCounts = isset($data['round_count']) || isset($data['flat_count']);
            $roundCount = max(0, (int) ($data['round_count'] ?? 0));
            $flatCount = max(0, (int) ($data['flat_count'] ?? 0));

            // Backward compatibility fallback for legacy callers passing single gallon_type + jug_count
            if (!$hasExplicitCounts || ($roundCount === 0 && $flatCount === 0)) {
                $legacyQty = max(0, (int) ($data['jug_count'] ?? 0));
                $legacyType = ucfirst(strtolower($data['gallon_type'] ?? ''));
                if ($legacyQty > 0) {
                    if ($legacyType === 'Flat') {
                        $flatCount = $legacyQty;
                        $roundCount = 0;
                    } else {
                        $roundCount = $legacyQty;
                        $flatCount = 0;
                    }
                }
            }

            $totalJugs = $roundCount + $flatCount;
            if ($totalJugs < 1) {
                throw new \InvalidArgumentException("Order must contain at least one Round or Flat gallon.");
            }

            // Always calculate price server-side using current system configuration
            $roundUnitPrice = $this->pricingService->getPrice('Round');
            $flatUnitPrice = $this->pricingService->getPrice('Flat');
            $totalAmount = round(($roundCount * $roundUnitPrice) + ($flatCount * $flatUnitPrice), 2);

            if ($roundCount > 0 && $flatCount > 0) {
                $gallonType = 'Mixed';
                $unitPrice = round($totalAmount / $totalJugs, 2);
            } elseif ($flatCount > 0) {
                $gallonType = 'Flat';
                $unitPrice = $flatUnitPrice;
            } else {
                $gallonType = 'Round';
                $unitPrice = $roundUnitPrice;
            }

            $paymentMethod = $data['payment_method'] ?? 'Cash';
            $paymentStatus = (strcasecmp($paymentMethod, 'Credit') === 0) ? 'Credit' : 'Unpaid';

            // Address fallback to customer's default address if empty
            $deliveryAddress = !empty(trim($data['delivery_address'] ?? ''))
                ? trim($data['delivery_address'])
                : $customer->address;

            $order = Order::create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'status' => 'Pending',
                'type' => $data['type'] ?? 'Walk-in',
                'jug_count' => $totalJugs,
                'round_count' => $roundCount,
                'flat_count' => $flatCount,
                'gallon_type' => $gallonType,
                'unit_price' => $unitPrice,
                'round_unit_price' => $roundUnitPrice,
                'flat_unit_price' => $flatUnitPrice,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'delivery_address' => $deliveryAddress,
                'preferred_time' => $data['preferred_time'] ?? null,
                'recurring' => (bool) ($data['recurring'] ?? false),
                'recurring_order_id' => $data['recurring_order_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'route_order' => 99,
            ]);

            $breakdownText = $gallonType === 'Mixed'
                ? sprintf('%d Round (@ ₱%.2f) + %d Flat (@ ₱%.2f) = %d Jugs', $roundCount, $roundUnitPrice, $flatCount, $flatUnitPrice, $totalJugs)
                : sprintf('%d x %s Gallon @ ₱%.2f', $totalJugs, $gallonType, $unitPrice);

            $this->activityLogService->log(
                action: 'Order Created',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf(
                    'Order #%d created for %s (%s, Total: ₱%.2f via %s)',
                    $order->id,
                    $customer->name,
                    $breakdownText,
                    $totalAmount,
                    $paymentMethod
                ),
                newValues: $order->toArray()
            );

            return $order;
        });
    }

    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            $order->update([
                'status' => 'Cancelled',
                'notes' => $reason ? ($order->notes . " | Cancelled: " . $reason) : $order->notes,
            ]);

            if ($order->delivery) {
                $order->delivery->update(['status' => 'Failed']);
            }

            $this->activityLogService->log(
                action: 'Order Cancelled',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf('Order #%d was cancelled. Reason: %s', $order->id, $reason ?: 'Not specified')
            );

            return $order;
        });
    }
}
