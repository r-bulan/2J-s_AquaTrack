<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Rider;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected DeliveryService $deliveryService,
        protected PricingService $pricingService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'All');
        $search = $request->query('search');

        $query = Order::with(['customer', 'rider', 'delivery', 'recurringOrder'])->orderBy('order_date', 'desc')->orderBy('id', 'desc');

        if ($status && $status !== 'All') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $riders = Rider::where('status', 'Active')->orderBy('name')->get();
        $prices = $this->pricingService->getPrices();

        $statusCounts = [
            'All' => Order::count(),
            'Pending' => Order::where('status', 'Pending')->count(),
            'Confirmed' => Order::where('status', 'Confirmed')->count(),
            'Out for Delivery' => Order::where('status', 'Out for Delivery')->count(),
            'Delivered' => Order::where('status', 'Delivered')->count(),
            'Cancelled' => Order::where('status', 'Cancelled')->count(),
        ];

        return view('orders.index', compact('orders', 'customers', 'riders', 'prices', 'status', 'statusCounts', 'search'));
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->validated());

        return redirect()->route('orders.index')->with('success', "Order #{$order->id} successfully placed!");
    }

    public function assignRider(Request $request, Order $order)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
            'route_order' => 'nullable|integer|min:1|max:999',
        ]);

        $rider = Rider::findOrFail($validated['rider_id']);
        $routeOrder = (int) ($validated['route_order'] ?? 99);

        $this->deliveryService->assignRiderToOrder($order, $rider, $routeOrder);

        return back()->with('success', "Rider {$rider->name} assigned to Order #{$order->id}!");
    }

    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->orderService->cancelOrder($order, $validated['reason'] ?? 'Cancelled by Admin');

        return back()->with('success', "Order #{$order->id} has been cancelled.");
    }
}
