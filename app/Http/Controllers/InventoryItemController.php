<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('category')
            ->where('funeral_home_id', auth()->id());

        $request->validate([
            'search'   => 'nullable|string|max:100',
            'status'   => 'nullable|in:all,available,in_use,maintenance',
            'category' => 'nullable|string|max:20',
        ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('inventory_category_id', $request->input('category'));
        }

        $categories = InventoryCategory::where('funeral_home_id', auth()->id())
            ->orderBy('name')->get();

        $items = $query->paginate(15)->withQueryString();

        return view('funeral.items.index', compact('items', 'categories'));
    }

    public function create()
    {
        $categories = InventoryCategory::where('funeral_home_id', auth()->id())
            ->orderBy('name')->get();
        return view('funeral.items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_category_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!InventoryCategory::where('id', $value)->where('funeral_home_id', auth()->id())->exists()) {
                        $fail('Invalid category selection.');
                    }
                },
            ],
            'name'                  => 'required|string|max:100',
            'brand'                 => 'nullable|string|max:100',
            'quantity'              => 'required|integer|min:0',
            'status'                => 'required|in:available,in_use,maintenance',
            'price'                 => 'nullable|numeric|min:0',
            'selling_price'         => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'shareable'             => 'boolean',
            'shareable_quantity' => 'nullable|integer|min:1',
            'low_stock_threshold'   => 'required|integer|min:1',
        ]);

        $validated['shareable'] = $request->has('shareable') ? 1 : 0;
        if (!$validated['shareable']) {
            $validated['shareable_quantity'] = null;
        }

        $item = new InventoryItem($validated);
        $item->funeral_home_id = auth()->id();
        $item->save();
        // Alert if quantity is below or equal to threshold
        if ($item->quantity <= $item->low_stock_threshold) {
            User::where('role', 'funeral')->where('id', auth()->id())->each(function ($user) use ($item) {
                $user->notify(new LowStockAlert($item));
            });
        }

        return redirect()->route('funeral.items.index')->with('success', 'Item added.');
    }

    public function edit(InventoryItem $item)
    {
        if ($item->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        $categories = InventoryCategory::where('funeral_home_id', auth()->id())
            ->orderBy('name')->get();
        return view('funeral.items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        if ($item->funeral_home_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'inventory_category_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!InventoryCategory::where('id', $value)->where('funeral_home_id', auth()->id())->exists()) {
                        $fail('Invalid category selection.');
                    }
                },
            ],
            'name'                  => 'required|string|max:100',
            'brand'                 => 'nullable|string|max:100',
            'quantity'              => 'required|integer|min:0',
            'status'                => 'required|in:available,in_use,maintenance',
            'price'                 => 'nullable|numeric|min:0',
            'selling_price'         => 'nullable|numeric|min:0',
            'expiry_date'           => 'nullable|date|after_or_equal:today',
            'shareable'             => 'boolean',
            'shareable_quantity' => 'nullable|integer|min:1',
            'low_stock_threshold'   => 'required|integer|min:1',
        ]);

        $validated['shareable'] = $request->has('shareable') ? 1 : 0;
        if (!$validated['shareable']) {
            $validated['shareable_quantity'] = null;
        }

        $item->update($validated);

        // Optional: Notify low stock
        if ($item->quantity <= $item->low_stock_threshold) {
            User::where('role', 'funeral')->where('id', auth()->id())->each(function ($user) use ($item) {
                $user->notify(new LowStockAlert($item));
            });
        }

        // IMPORTANT: Update all affected packages with new total
        \App\Http\Controllers\PackageController::updatePackagesWithItem($item->id);

        return redirect()->route('funeral.items.index')->with('success', 'Item updated.');
    }


    public function destroy(InventoryItem $item)
    {
        if ($item->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        $item->delete();
        return redirect()->route('funeral.items.index')->with('success', 'Item deleted.');
    }

    public function adjustStock(Request $request, InventoryItem $item)
    {
        if ($item->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        $validated = $request->validate([
            'type'     => 'required|in:inbound,outbound',
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:255',
        // 'shareable_quantity' => 'nullable|integer|min:1', // Only add this if you want to allow changing shareable_quantity here


        ]);

        $change = $validated['type'] === 'inbound' ? $validated['quantity'] : -$validated['quantity'];
        $item->quantity += $change;
        $item->save();

        InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'type'              => $validated['type'],
            'quantity'          => $validated['quantity'],
            'reason'            => $validated['reason'],
            'funeral_home_id'   => auth()->id(),
            'shareable_quantity' => $item->shareable_quantity,
        ]);

        // Alert if quantity is below or equal to threshold
        if ($item->quantity <= $item->low_stock_threshold) {
            User::where('role', 'funeral')->where('id', auth()->id())->each(function ($user) use ($item) {
                $user->notify(new LowStockAlert($item));
            });
        }

        if ($item->shareable) {
            $item->shareable_quantity = $request->input('shareable_quantity', $item->shareable_quantity);
        } else {
            $item->shareable_quantity = null;
        }
        $item->save();


        return redirect()->back()->with('success', 'Stock adjusted successfully.');
    }

    public function movements(InventoryItem $item)
    {
        if ($item->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        $movements = $item->movements()->with('user')->latest()->paginate(10);
        return view('funeral.items.movements', compact('item', 'movements'));
    }

    public function export(Request $request)
    {
        $query = InventoryItem::with('category')->where('funeral_home_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('inventory_category_id', $request->input('category'));
        }

        $items = $query->get();

        $csvHeader = [
            'ID', 'Name', 'Category', 'Brand', 'Quantity', 'Status', 'Price', 'Selling Price', 'Shareable', 'Shareable Qty', 'Created At'
        ];


        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->id,
                $item->name,
                $item->category->name ?? 'Uncategorized',
                $item->brand,
                $item->quantity,
                $item->status,
                $item->price,
                $item->selling_price,         // <--- Added this line
                $item->shareable ? 'Yes' : 'No',
                $item->shareable_quantity ?? '-',
                $item->created_at,
            ];
        }

        $filename = 'inventory-items-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function() use ($csvHeader, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache'
        ]);
    }
}
