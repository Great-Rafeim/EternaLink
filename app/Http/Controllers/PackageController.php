<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\PackageAssetCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

class PackageController extends Controller
{
    public function index()
    {
        $packages = ServicePackage::where('funeral_home_id', auth()->id())->get();
        return view('funeral.packages.index', compact('packages'));
    }

    public static function updatePackagesWithItem($itemId)
    {
        $packageIds = DB::table('service_package_components')
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
        $funeralHomeId = auth()->id();
        $categories = InventoryCategory::where('funeral_home_id', $funeralHomeId)->get();
        $items = InventoryItem::where('status', 'available')->where('funeral_home_id', $funeralHomeId)->get();

        $itemsByCategory = [];
        foreach ($categories as $category) {
            $itemsByCategory[$category->id] = $items
                ->where('inventory_category_id', $category->id)
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => (float)$item->selling_price,
                    ];
                })->values()->toArray();
        }

        // For easy JS asset selection: only bookable categories
        $assetCategories = $categories->where('is_asset', true)->values();

        return view('funeral.packages.create', compact('categories', 'itemsByCategory', 'assetCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'assets' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'max:20480'],
        ]);

        $totalPrice = 0;
        $packageItemData = [];
        $assetCategoryData = [];

        // --- DEBUG: Log raw input
        \Log::info('PACKAGE_DEBUG - assets from request', ['assets' => $request->input('assets')]);
        \Log::info('PACKAGE_DEBUG - items from request', ['items' => $request->input('items')]);

        // Items (consumables)
        if ($request->filled('items')) {
            foreach ($request->input('items', []) as $catId => $catItems) {
                foreach ($catItems as $itemData) {
                    $item = InventoryItem::findOrFail($itemData['id']);
                    $qty = max(1, intval($itemData['quantity']));
                    $price = $item->selling_price * $qty;
                    $totalPrice += $price;
                    $packageItemData[] = [
                        'inventory_item_id' => $item->id,
                        'quantity' => $qty,
                    ];
                }
            }
        }

        // Bookable Asset Categories: each must have a category_id and price
        if ($request->filled('assets')) {
            foreach ($request->input('assets', []) as $asset) {
                \Log::info('PACKAGE_DEBUG - inspecting asset in loop', ['asset' => $asset]);
                if (!empty($asset['category_id']) && is_numeric($asset['price'])) {
                    $totalPrice += floatval($asset['price']);
                    $assetCategoryData[] = [
                        'inventory_category_id' => $asset['category_id'],
                        'price' => floatval($asset['price']),
                    ];
                }
            }
        }

        // --- DEBUG: Log processed asset category data
        \Log::info('PACKAGE_DEBUG - assetCategoryData parsed', ['assetCategoryData' => $assetCategoryData]);
        \Log::info('PACKAGE_DEBUG - packageItemData parsed', ['packageItemData' => $packageItemData]);
        \Log::info('PACKAGE_DEBUG - totalPrice', ['totalPrice' => $totalPrice]);

        $packageData = [
            'funeral_home_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'total_price' => $totalPrice,
        ];

        if ($request->hasFile('image')) {
            $packageData['image'] = $request->file('image')->store('service_packages', 'public');
        }

        DB::beginTransaction();
        try {
            $package = ServicePackage::create($packageData);

            // Attach consumable items (pivot)
            foreach ($packageItemData as $data) {
                $package->items()->attach($data['inventory_item_id'], [
                    'quantity' => $data['quantity'],
                ]);
            }

            // Attach asset categories (new table)
            foreach ($assetCategoryData as $data) {
                \Log::info('PACKAGE_DEBUG - inserting asset category row', [
                    'service_package_id' => $package->id,
                    'inventory_category_id' => $data['inventory_category_id'],
                    'price' => $data['price'],
                ]);
                PackageAssetCategory::create([
                    'service_package_id' => $package->id,
                    'inventory_category_id' => $data['inventory_category_id'],
                    'price' => $data['price'],
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('PACKAGE_DEBUG - failed to create package', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to create package: ' . $e->getMessage()]);
        }

        return redirect()->route('funeral.packages.index')->with('success', 'Package created successfully!');
    }

    public function edit($id)
    {
        $funeralHomeId = auth()->id();
        $package = ServicePackage::with(['items.category', 'assetCategories.inventoryCategory'])
            ->where('funeral_home_id', $funeralHomeId)
            ->findOrFail($id);

        $categories = InventoryCategory::where('funeral_home_id', $funeralHomeId)->get();
        $items = InventoryItem::where('status', 'available')
            ->where('funeral_home_id', $funeralHomeId)
            ->get();

        $itemsByCategory = [];
        foreach ($categories as $category) {
            $itemsByCategory[$category->id] = $items
                ->where('inventory_category_id', $category->id)
                ->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float)$item->selling_price,
                ])->values()->toArray();
        }

        // For JS: prefill selections
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
        // Prefill assets: [{category_id, price}]
        $currentAssets = $package->assetCategories->map(function($pac) {
            return [
                'category_id' => $pac->inventory_category_id,
                'price' => $pac->price,
            ];
        })->toArray();

        $assetCategories = $categories->where('is_asset', true)->values();

        return view('funeral.packages.edit', compact('package', 'categories', 'itemsByCategory', 'currentSelection', 'currentAssets', 'assetCategories'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'assets' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'max:20480'],
        ]);

        $package = ServicePackage::where('funeral_home_id', auth()->id())->findOrFail($id);

        $totalPrice = 0;
        $syncData = [];
        $assetCategoryData = [];

        // Items
        if ($request->filled('items')) {
            foreach ($request->input('items', []) as $catId => $catItems) {
                foreach ($catItems as $itemData) {
                    $item = InventoryItem::findOrFail($itemData['id']);
                    $qty = max(1, intval($itemData['quantity']));
                    $totalPrice += $item->selling_price * $qty;
                    $syncData[$item->id] = ['quantity' => $qty];
                }
            }
        }

        // Asset categories
        if ($request->filled('assets')) {
            foreach ($request->input('assets', []) as $asset) {
                if (!empty($asset['category_id']) && is_numeric($asset['price'])) {
                    $totalPrice += floatval($asset['price']);
                    $assetCategoryData[] = [
                        'inventory_category_id' => $asset['category_id'],
                        'price' => floatval($asset['price']),
                    ];
                }
            }
        }

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'total_price' => $totalPrice,
        ];

        if ($request->input('remove_image') == "1" && $package->image) {
            \Storage::disk('public')->delete($package->image);
            $updateData['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($package->image) {
                \Storage::disk('public')->delete($package->image);
            }
            $updateData['image'] = $request->file('image')->store('service_packages', 'public');
        }

        DB::beginTransaction();
        try {
            $package->update($updateData);
            $package->items()->sync($syncData);

            // Remove all old asset category prices
            PackageAssetCategory::where('service_package_id', $package->id)->delete();

            // Insert new asset category prices
            foreach ($assetCategoryData as $data) {
                PackageAssetCategory::create([
                    'service_package_id' => $package->id,
                    'inventory_category_id' => $data['inventory_category_id'],
                    'price' => $data['price'],
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update package: ' . $e->getMessage()]);
        }

        return redirect()->route('funeral.packages.index')->with('success', 'Package updated successfully!');
    }

    public function destroy($id)
    {
        $package = ServicePackage::where('funeral_home_id', auth()->id())->findOrFail($id);

        $package->items()->detach();
        // Remove related asset categories (cleanup)
        PackageAssetCategory::where('service_package_id', $package->id)->delete();

        $package->delete();

        return redirect()->route('funeral.packages.index')
            ->with('success', 'Package deleted successfully!');
    }
}
