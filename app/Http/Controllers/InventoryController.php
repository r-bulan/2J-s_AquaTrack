<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestockRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request)
    {
        $category = $request->query('category', 'All');
        $search = $request->query('search');

        $query = InventoryItem::orderBy('name');

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->get();
        $categories = ['Gallons', 'Caps', 'Seals', 'Filters', 'Chemicals', 'Other'];

        $lowStockCount = InventoryItem::whereColumn('quantity', '<=', 'reorder_threshold')->count();
        $totalItemsCount = InventoryItem::count();

        return view('inventory.index', compact('items', 'categories', 'category', 'search', 'lowStockCount', 'totalItemsCount'));
    }

    public function store(StoreInventoryItemRequest $request)
    {
        $item = InventoryItem::create($request->validated());

        $this->activityLogService->log(
            action: 'Inventory Item Created',
            entityType: 'InventoryItem',
            entityId: $item->id,
            description: sprintf('Added inventory item: %s (%s, %d %s)', $item->name, $item->category, $item->quantity, $item->unit),
            newValues: $item->toArray()
        );

        return redirect()->route('inventory.index')->with('success', "Inventory item '{$item->name}' added!");
    }

    public function restock(RestockRequest $request, InventoryItem $item)
    {
        $validated = $request->validated();
        $addQuantity = (int) $validated['quantity'];
        $oldQuantity = $item->quantity;

        $item->update([
            'quantity' => $oldQuantity + $addQuantity,
            'last_restocked' => now()->toDateString(),
        ]);

        $this->activityLogService->log(
            action: 'Inventory Restocked',
            entityType: 'InventoryItem',
            entityId: $item->id,
            description: sprintf('Restocked %s: +%d %s (was %d, now %d)', $item->name, $addQuantity, $item->unit, $oldQuantity, $item->quantity),
            oldValues: ['quantity' => $oldQuantity],
            newValues: ['quantity' => $item->quantity]
        );

        return back()->with('success', "Restocked {$addQuantity} {$item->unit} of {$item->name}!");
    }
}
