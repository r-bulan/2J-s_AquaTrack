<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\StoreRecurringOrderRequest;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\RecurringOrder;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\RecurringOrderService;
use App\Services\ReorderForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected OrderService $orderService,
        protected ReorderForecastService $reorderForecastService,
        protected RecurringOrderService $recurringOrderService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            // Automatically initialize customer profile if somehow missing
            $customer = $user->customer()->create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => 'N/A',
                'address' => 'Customer Address',
                'status' => 'Active',
            ]);
            $customer->jugLedger()->create(['customer_name' => $customer->name]);
            $customer->creditLedger()->create(['customer_name' => $customer->name]);
            $customer->loyaltyRecord()->create(['customer_name' => $customer->name]);
        }

        $customer->load(['jugLedger', 'creditLedger', 'loyaltyRecord']);

        $tab = $request->query('tab', 'home'); // home, place-order, recurring, orders, feedback

        // Predictive refill reminder
        $refillForecast = $this->reorderForecastService->checkCustomerRefillDue($customer);

        // Prices for ordering
        $prices = $this->pricingService->getPrices();

        // Customer's Orders
        $orders = Order::where('customer_id', $customer->id)
            ->with(['rider', 'delivery'])
            ->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'orders_page');

        // Customer's Recurring Orders
        $recurringOrders = RecurringOrder::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Customer's Feedback
        $feedbackList = Feedback::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('portal.index', compact(
            'customer',
            'tab',
            'refillForecast',
            'prices',
            'orders',
            'recurringOrders',
            'feedbackList'
        ));
    }

    public function placeOrder(PlaceOrderRequest $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $data = $request->validated();
        $data['customer_id'] = $customer->id;
        $data['type'] = 'Online';
        $data['order_date'] = now()->toDateString();

        $order = $this->orderService->createOrder($data);

        return redirect()->route('portal.index', ['tab' => 'orders'])
            ->with('success', "Your order #{$order->id} ({$order->breakdown}) has been placed successfully!");
    }

    public function storeRecurringOrder(StoreRecurringOrderRequest $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $recurringOrder = $this->recurringOrderService->createRecurringOrder($request->validated(), $customer);

        return redirect()->route('portal.index', ['tab' => 'recurring'])
            ->with('success', "Recurring refill schedule #{$recurringOrder->id} ({$recurringOrder->breakdown}) created successfully!");
    }

    public function pauseRecurringOrder(RecurringOrder $recurringOrder)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $this->recurringOrderService->pauseRecurringOrder($recurringOrder, $customer);

        return redirect()->route('portal.index', ['tab' => 'recurring'])
            ->with('success', "Recurring schedule #{$recurringOrder->id} has been paused.");
    }

    public function resumeRecurringOrder(RecurringOrder $recurringOrder)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $this->recurringOrderService->resumeRecurringOrder($recurringOrder, $customer);

        return redirect()->route('portal.index', ['tab' => 'recurring'])
            ->with('success', "Recurring schedule #{$recurringOrder->id} has been resumed. Next delivery scheduled on {$recurringOrder->fresh()->next_order_date->format('M d, Y')}.");
    }

    public function cancelRecurringOrder(RecurringOrder $recurringOrder)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $this->recurringOrderService->cancelRecurringOrder($recurringOrder, $customer);

        return redirect()->route('portal.index', ['tab' => 'recurring'])
            ->with('success', "Recurring schedule #{$recurringOrder->id} has been cancelled.");
    }

    public function submitFeedback(StoreFeedbackRequest $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        Feedback::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'type' => $request->input('type'),
            'rating' => $request->input('rating'),
            'message' => $request->input('message'),
            'status' => 'Open',
        ]);

        return redirect()->route('portal.index', ['tab' => 'feedback'])
            ->with('success', 'Thank you for your feedback! Our team will review it promptly.');
    }
}
