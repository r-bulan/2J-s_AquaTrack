<?php

namespace App\Http\Controllers;

use App\Models\CreditLedger;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\ReorderForecastService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ReorderForecastService $reorderForecastService
    ) {}

    public function index()
    {
        $today = now()->toDateString();

        // 1. Statistics Cards
        $todayOrders = Order::whereDate('order_date', $today)->get();
        $todayOrdersCount = $todayOrders->count();
        $todayDeliveredCount = $todayOrders->where('status', 'Delivered')->count();

        $todayRevenue = (float) Transaction::where('type', 'income')
            ->whereDate('date', $today)
            ->sum('amount');

        $pendingDeliveriesCount = Order::whereIn('status', ['Confirmed', 'Out for Delivery'])->count();

        $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'reorder_threshold')->get();
        $lowStockCount = $lowStockItems->count();

        // 2. 7-Day Income vs Expense Chart Data
        $chartDates = [];
        $chartLabels = [];
        $chartIncome = [];
        $chartExpense = [];
        $chartOrdersCount = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->toDateString();
            $label = $date->format('M d');

            $chartDates[] = $dateStr;
            $chartLabels[] = $label;

            $income = (float) Transaction::where('type', 'income')
                ->whereDate('date', $dateStr)
                ->sum('amount');

            $expense = (float) Transaction::where('type', 'expense')
                ->whereDate('date', $dateStr)
                ->sum('amount');

            $ordersCount = Order::whereDate('order_date', $dateStr)->count();

            $chartIncome[] = $income;
            $chartExpense[] = $expense;
            $chartOrdersCount[] = $ordersCount;
        }

        // 3. Smart Reorder Alerts (Up to 6 customers)
        $dueCustomers = $this->reorderForecastService->getDashboardDueCustomers(6);

        // 4. Alerts and Action Items (Up to 3 low stock items, up to 3 overdue credit)
        $topLowStock = $lowStockItems->take(3);
        $overdueCreditCustomers = CreditLedger::where('amount_owed', '>', 0)
            ->where(function ($q) {
                $q->where('status', 'Overdue')
                    ->orWhere('amount_owed', '>=', 500); // Highlight significant utang
            })
            ->orderByDesc('amount_owed')
            ->take(3)
            ->get();

        return view('dashboard.index', compact(
            'todayOrdersCount',
            'todayDeliveredCount',
            'todayRevenue',
            'pendingDeliveriesCount',
            'lowStockCount',
            'chartLabels',
            'chartIncome',
            'chartExpense',
            'chartOrdersCount',
            'dueCustomers',
            'topLowStock',
            'overdueCreditCustomers'
        ));
    }
}
