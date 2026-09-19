<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request)
    {
        $typeFilter = $request->query('type');
        $search = $request->query('search');

        // All-Time Stats
        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpenses = (float) Transaction::where('type', 'expense')->sum('amount');
        $netProfit = $totalIncome - $totalExpenses;

        // 6-Month Chart Data
        $sixMonthLabels = [];
        $sixMonthIncome = [];
        $sixMonthExpenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $sixMonthLabels[] = $month->format('M Y');

            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();

            $income = (float) Transaction::where('type', 'income')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $expense = (float) Transaction::where('type', 'expense')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $sixMonthIncome[] = $income;
            $sixMonthExpenses[] = $expense;
        }

        // Expense Breakdown by Category (Donut Chart)
        $expenseCategories = Transaction::where('type', 'expense')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->toArray();

        // Transactions Table
        $query = Transaction::orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($typeFilter && in_array($typeFilter, ['income', 'expense'])) {
            $query->where('type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('finance.index', compact(
            'totalIncome',
            'totalExpenses',
            'netProfit',
            'sixMonthLabels',
            'sixMonthIncome',
            'sixMonthExpenses',
            'expenseCategories',
            'transactions',
            'typeFilter',
            'search'
        ));
    }

    public function store(StoreTransactionRequest $request)
    {
        $transaction = Transaction::create($request->validated());

        $this->activityLogService->log(
            action: 'Transaction Created',
            entityType: 'Transaction',
            entityId: $transaction->id,
            description: sprintf('Recorded manual %s of ₱%.2f (%s: %s)', $transaction->type, $transaction->amount, $transaction->category, $transaction->description),
            newValues: $transaction->toArray()
        );

        return redirect()->route('finance.index')->with('success', ucfirst($transaction->type) . " transaction of ₱" . number_format($transaction->amount, 2) . " recorded!");
    }
}
