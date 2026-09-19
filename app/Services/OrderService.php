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

            $gallonType = in_array(ucfirst(strtolower($data['gallon_type'] ?? '')), ['Round', 'Flat'])
                ? ucfirst(strtolower($data['gallon_type']))
                : 'Round';

            $quantity = max(1, (int) ($data['jug_count'] ?? 1));
            $unitPrice = $this->pricingService->getPrice($gallonType);
            $totalAmount = round($unitPrice * $quantity, 2);

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
                'jug_count' => $quantity,
                'gallon_type' => $gallonType,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'delivery_address' => $deliveryAddress,
                'preferred_time' => $data['preferred_time'] ?? null,
                'recurring' => (bool) ($data['recurring'] ?? false),
                'notes' => $data['notes'] ?? null,
                'route_order' => 99,
            ]);

            $this->activityLogService->log(
                action: 'Order Created',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf(
                    'Order #%d created for %s (%d x %s Gallon @ ₱%.2f = ₱%.2f via %s)',
                    $order->id,
                    $customer->name,
                    $quantity,
                    $gallonType,
                    $unitPrice,
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
