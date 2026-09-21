<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustCustomerJugsRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLogService;
use App\Services\CustomerSyncService;
use App\Services\ReorderForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected ReorderForecastService $reorderForecastService,
        protected CustomerSyncService $customerSyncService
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Customer::with(['jugLedger', 'creditLedger', 'loyaltyRecord', 'orders'])
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(12)->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['jugLedger', 'creditLedger', 'loyaltyRecord', 'orders', 'feedback']);
        $reorderStatus = $this->reorderForecastService->checkCustomerRefillDue($customer);

        return response()->json([
            'customer' => $customer,
            'reorder_status' => $reorderStatus,
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $customer = DB::transaction(function () use ($validated) {
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'],
                'barangay' => $validated['barangay'] ?? null,
                'area' => $validated['area'] ?? null,
                'jug_deposit' => $validated['jug_deposit'] ?? 0,
                'status' => 'Active',
                'avg_reorder_days' => $validated['avg_reorder_days'] ?? 7,
                'notes' => $validated['notes'] ?? null,
            ]);

            $depositAmount = (float) ($validated['jug_deposit'] ?? 0);
            $customer->jugLedger()->create([
                'customer_name' => $customer->name,
                'jugs_held' => 0,
                'deposit_status' => $depositAmount > 0 ? 'Paid' : 'Unpaid',
                'deposit_amount' => $depositAmount,
            ]);

            $customer->creditLedger()->create([
                'customer_name' => $customer->name,
                'amount_owed' => 0,
                'status' => 'Settled',
            ]);

            $customer->loyaltyRecord()->create([
                'customer_name' => $customer->name,
                'refills_count' => 0,
                'refills_needed' => 10,
                'free_jugs_earned' => 0,
            ]);

            $this->activityLogService->log(
                action: 'Customer Created',
                entityType: 'Customer',
                entityId: $customer->id,
                description: sprintf('Created new customer %s (%s)', $customer->name, $customer->phone),
                newValues: $customer->toArray()
            );

            return $customer;
        });

        return redirect()->route('customers.index')->with('success', "Customer {$customer->name} successfully registered!");
    }

    /**
     * Manually adjust customer's jug balance (Admin / Owner only).
     */
    public function adjustJugs(AdjustCustomerJugsRequest $request, Customer $customer)
    {
        $validated = $request->validated();
        $newBalance = (int) $validated['new_balance'];
        $reason = $validated['reason'];

        $this->customerSyncService->manuallyAdjustJugBalance($customer, $newBalance, $reason);

        return back()->with('success', "Jug balance for {$customer->name} has been manually updated to {$newBalance}.");
    }
}
