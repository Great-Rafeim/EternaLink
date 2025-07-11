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
        ->where('inventory_items.funeral_home_id', auth()->id());

    $request->validate([
        'search'         => 'nullable|string|max:100',
        'status'         => 'nullable|in:all,available,in_use,maintenance,reserved,shared_to_partner,borrowed_from_partner',
        'category'       => 'nullable|string|max:20',
        'sort'           => 'nullable|string|max:50',
        'direction'      => 'nullable|in:asc,desc',
        'shareable_only' => 'nullable|boolean',
        'low_stock_only' => 'nullable|boolean',
    ]);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('inventory_items.name', 'like', "%{$search}%")
              ->orWhere('inventory_items.brand', 'like', "%{$search}%");
        });
    }
    if ($request->filled('status') && $request->input('status') !== 'all') {
        $query->where('inventory_items.status', $request->input('status'));
    }
    if ($request->filled('category') && $request->input('category') !== 'all') {
        if ($request->input('category') === 'none') {
            $query->whereNull('inventory_items.inventory_category_id');
        } elseif (is_numeric($request->input('category'))) {
            $query->where('inventory_items.inventory_category_id', (int) $request->input('category'));
        }
    }
    // Shareable only filter
    if ($request->boolean('shareable_only')) {
        $query->where('inventory_items.shareable', 1);
    }
    // Low stock only filter
    if ($request->boolean('low_stock_only')) {
        $query->whereColumn('inventory_items.quantity', '<=', 'inventory_items.low_stock_threshold')
              ->whereNotNull('inventory_items.low_stock_threshold');
    }

    $sortable = [
        'name', 'brand', 'quantity', 'low_stock_threshold', 'price', 'selling_price', 'expiry_date', 'status', 'shareable_quantity'
    ];

    // Sorting logic
    if ($request->filled('sort') && in_array($request->input('sort'), $sortable)) {
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $query->orderBy("inventory_items.$sort", $direction);
    } elseif ($request->input('sort') === 'category') {
        $direction = $request->input('direction', 'asc');
        $query->leftJoin('inventory_categories as cat', 'cat.id', '=', 'inventory_items.inventory_category_id')
            ->where('inventory_items.funeral_home_id', auth()->id())
            ->orderBy('cat.name', $direction)
            ->select('inventory_items.*');
    } else {
        // Default: ascending by name (for predictable table order)
        $query->orderBy('inventory_items.name', 'asc');
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
        $category = InventoryCategory::where('id', $request->inventory_category_id)
            ->where('funeral_home_id', auth()->id())
            ->firstOrFail();

        // Validation rules
        $rules = [
            'inventory_category_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!InventoryCategory::where('id', $value)->where('funeral_home_id', auth()->id())->exists()) {
                        $fail('Invalid category selection.');
                    }
                },
            ],
            'name'          => 'required|string|max:100',
            'brand'         => 'nullable|string|max:100',
            'status'        => 'required|in:available,in_use,maintenance',
            'price'         => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'image'         => 'nullable|image|max:20480', // <--- ADD THIS
        ];

        if ($category->is_asset) {
            // Bookable asset: ignore quantity, low stock, expiry, sharing
        } else {
            $rules['quantity'] = 'required|integer|min:0';
            $rules['low_stock_threshold'] = 'required|integer|min:1';
            $rules['expiry_date'] = 'nullable|date|after_or_equal:today';
            $rules['shareable'] = 'boolean';
            $rules['shareable_quantity'] = 'nullable|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Assign base fields
        $item = new InventoryItem($validated);
        $item->funeral_home_id = auth()->id();

        if ($category->is_asset) {
            $item->quantity = 1;
            $item->low_stock_threshold = null;
            $item->expiry_date = null;
            // Allow assets to be shareable (resource shared) if checkbox is set
            $item->shareable = $request->has('shareable') ? 1 : 0;
            $item->shareable_quantity = null; // Usually not used for assets, keep as null
        } else {
            $item->shareable = $request->has('shareable') ? 1 : 0;
            if (!$item->shareable) {
                $item->shareable_quantity = null;
            }
        }

        // IMAGE UPLOAD HANDLING
        if ($request->hasFile('image')) {
            $item->image = $request->file('image')->store('inventory_items', 'public');
        }

        $item->save();

        // Low stock alert for consumables
        if (!$category->is_asset && $item->quantity <= $item->low_stock_threshold) {
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

    $category = InventoryCategory::where('id', $request->inventory_category_id)
        ->where('funeral_home_id', auth()->id())
        ->firstOrFail();

    // Force shareable_quantity to null if shareable is NOT checked (no matter what was posted)
    if (!$request->has('shareable') || !$request->input('shareable')) {
        $request->merge(['shareable_quantity' => null]);
    }

    $rules = [
        'inventory_category_id' => [
            'required',
            function ($attribute, $value, $fail) {
                if (!InventoryCategory::where('id', $value)->where('funeral_home_id', auth()->id())->exists()) {
                    $fail('Invalid category selection.');
                }
            },
        ],
        'name'          => 'required|string|max:100',
        'brand'         => 'nullable|string|max:100',
        'status'        => 'required|in:available,in_use,maintenance,reserved',
        'price'         => 'nullable|numeric|min:0',
        'selling_price' => 'nullable|numeric|min:0',
        'image'         => 'nullable|image|max:20480',
    ];

    if ($category->is_asset) {
        // Asset logic handled below
    } else {
        $rules['quantity'] = 'required|integer|min:0';
        $rules['low_stock_threshold'] = 'required|integer|min:1';
        $rules['expiry_date'] = 'nullable|date|after_or_equal:today';
        $rules['shareable'] = 'nullable|boolean';
        // Only require shareable_quantity if shareable is checked!
        $rules['shareable_quantity'] = [
            'nullable',
            'integer',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->has('shareable') && $request->input('shareable')) {
                    if (is_null($value) || $value < 1) {
                        $fail('Shareable quantity must be at least 1 when sharing is enabled.');
                    }
                }
            }
        ];
    }

    $validated = $request->validate($rules);

    $item->fill($validated);

    if ($category->is_asset) {
        $item->quantity = 1;
        $item->low_stock_threshold = null;
        $item->expiry_date = null;
        $item->shareable = $request->has('shareable') ? 1 : 0;
        $item->shareable_quantity = null;
    } else {
        $item->shareable = $request->has('shareable') ? 1 : 0;
        $item->shareable_quantity = $item->shareable ? (int) $request->input('shareable_quantity') : null;
    }

    // Image logic (unchanged)
    if ($request->input('remove_image') == "1" && $item->image) {
        \Storage::disk('public')->delete($item->image);
        $item->image = null;
    }
    if ($request->hasFile('image')) {
        if ($item->image) {
            \Storage::disk('public')->delete($item->image);
        }
        $item->image = $request->file('image')->store('inventory_items', 'public');
    }

    $item->save();

    // Low stock alert for consumables
    if (!$category->is_asset && $item->quantity <= $item->low_stock_threshold) {
        User::where('role', 'funeral')->where('id', auth()->id())->each(function ($user) use ($item) {
            $user->notify(new LowStockAlert($item));
        });
    }

    \App\Http\Controllers\PackageController::updatePackagesWithItem($item->id);

    return redirect()->route('funeral.items.index')->with('success', 'Item updated.');
}





public function destroy(InventoryItem $item)
{
    try {
        \Log::debug('[destroy] Called', [
            'item_id' => $item->id,
            'funeral_home_id' => $item->funeral_home_id,
            'auth_id' => auth()->id(),
            'image' => $item->image,
        ]);
        if ($item->funeral_home_id !== auth()->id()) {
            \Log::warning('[destroy] Unauthorized', [
                'item_id' => $item->id,
                'expected' => $item->funeral_home_id,
                'actual' => auth()->id()
            ]);
            abort(403);
        }

        // Unlink from resource requests if you want to allow deletion
        \App\Models\ResourceRequest::where('requested_item_id', $item->id)
            ->update(['requested_item_id' => null]);
        \App\Models\ResourceRequest::where('provider_item_id', $item->id)
            ->update(['provider_item_id' => null]);

        if ($item->image) {
            \Log::debug('[destroy] Deleting image', ['image' => $item->image]);
            \Storage::disk('public')->delete($item->image);
        }

        $item->delete();
        \Log::debug('[destroy] Item deleted', ['item_id' => $item->id]);
        return redirect()->route('funeral.items.index')->with('success', 'Item deleted.');
    } catch (\Exception $e) {
        \Log::error('[destroy] Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return back()->withErrors(['error' => 'Failed to delete item: ' . $e->getMessage()]);
    }
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

        // Update shareable quantity if enabled
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
                $item->selling_price,
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
