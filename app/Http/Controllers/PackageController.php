<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 


class PackageController extends Controller
{

    public function index()
    {
        // Fetch packages for the logged-in funeral home
        $packages = \App\Models\ServicePackage::where('funeral_home_id', auth()->id())->get();

        return view('funeral.packages.index', compact('packages'));
    }

    public static function updatePackagesWithItem($itemId)
{
    // Find all service packages that use this item
    $packageIds = DB::table('inventory_item_service_package')
        ->where('inventory_item_id', $itemId)
        ->pluck('service_package_id');

    foreach ($packageIds as $packageId) {
        $package = ServicePackage::with('items')->find($packageId);
        if ($package) {
            $newTotal = $package->items->sum(function ($item) {
                return ($item->selling_price ?? 0) * ($item->pivot->quantity ?? 1);
            });
            $package->update(['total_price' => $newTotal]);
        }
    }
}


    public function create()
    {
        // Fetch all categories
        $categories = InventoryCategory::all();

        // Group items by category id for modal
        $itemsByCategory = [];
        $items = InventoryItem::where('status', 'available')->get();
        foreach ($categories as $category) {
            $itemsByCategory[$category->id] = $items->where('inventory_category_id', $category->id)
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => (float)$item->selling_price,
                    ];
                })->values()->toArray();
        }

        return view('funeral.packages.create', compact('categories', 'itemsByCategory'));
    }

    public function store(Request $request)
    {
        // Validate package info
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array'],
        ]);

        $items = $request->input('items');
        $totalPrice = 0;
        $packageItemsData = [];

        foreach ($items as $catId => $catItems) {
            foreach ($catItems as $itemData) {
                // Validate existence and get price from DB
                $item = InventoryItem::findOrFail($itemData['id']);
                $qty = max(1, intval($itemData['quantity']));
                $price = $item->selling_price * $qty;
                $totalPrice += $price;
                $packageItemsData[] = [
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                ];
            }
        }

        // Create package and attach items (within transaction)
        DB::beginTransaction();
        try {
            $package = ServicePackage::create([
                'funeral_home_id' => auth()->id(), // adjust as needed
                'name' => $request->name,
                'description' => $request->description,
                'total_price' => $totalPrice,
            ]);

            foreach ($packageItemsData as $data) {
                $package->items()->attach($data['inventory_item_id'], ['quantity' => $data['quantity']]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create package: ' . $e->getMessage()]);
        }

        return redirect()->route('funeral.packages.index')
            ->with('success', 'Package created successfully!');
    }

public function edit($id)
{
    $package = ServicePackage::with(['items.category'])->where('funeral_home_id', auth()->id())->findOrFail($id);

    // All categories and items (grouped by category)
    $categories = InventoryCategory::all();
    $itemsByCategory = [];
    $items = InventoryItem::where('status', 'available')->get();
    foreach ($categories as $category) {
        $itemsByCategory[$category->id] = $items->where('inventory_category_id', $category->id)
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float)$item->selling_price,
            ])->values()->toArray();
    }

    // Prepare current selection structure for JS
    $currentSelection = [];
    foreach ($package->items as $item) {
        $catId = $item->category->id ?? null;
        if (!$catId) continue;
        $currentSelection[$catId][] = [
            'id' => $item->id,
            'name' => $item->name,
            'price' => (float)$item->selling_price,
            'quantity' => $item->pivot->quantity ?? 1,
        ];
    }

    return view('funeral.packages.edit', compact('package', 'categories', 'itemsByCategory', 'currentSelection'));
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'items' => ['required', 'array'],
    ]);

    $package = ServicePackage::where('funeral_home_id', auth()->id())->findOrFail($id);

    $items = $request->input('items');
    $totalPrice = 0;
    $syncData = [];

    foreach ($items as $catId => $catItems) {
        foreach ($catItems as $itemData) {
            $item = InventoryItem::findOrFail($itemData['id']);
            $qty = max(1, intval($itemData['quantity']));
            $totalPrice += $item->selling_price * $qty;
            $syncData[$item->id] = ['quantity' => $qty];
        }
    }

    $package->update([
        'name' => $request->name,
        'description' => $request->description,
        'total_price' => $totalPrice,
    ]);
    $package->items()->sync($syncData);

    return redirect()->route('funeral.packages.index')->with('success', 'Package updated successfully!');
}

public function destroy($id)
{
    $package = \App\Models\ServicePackage::where('funeral_home_id', auth()->id())->findOrFail($id);

    // Optional: detach all related items (not required if set up for cascade delete)
    $package->items()->detach();

    $package->delete();

    return redirect()->route('funeral.packages.index')
        ->with('success', 'Package deleted successfully!');
}



}

