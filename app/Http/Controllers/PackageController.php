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
        $funeralHomeId = auth()->id();

        // Fetch only categories owned by this funeral home
        $categories = InventoryCategory::where('funeral_home_id', $funeralHomeId)->get();

        // Fetch only items owned by this funeral home
        $items = InventoryItem::where('status', 'available')
            ->where('funeral_home_id', $funeralHomeId)
            ->get();

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

        return view('funeral.packages.create', compact('categories', 'itemsByCategory'));
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'image' => ['nullable', 'image', 'max:20480'], // add validation rule for image
        ]);

        $items = $request->input('items');
        $totalPrice = 0;
        $packageItemsData = [];

        foreach ($items as $catId => $catItems) {
            foreach ($catItems as $itemData) {
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

        // Prepare the data to save
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
        $funeralHomeId = auth()->id();

        $package = ServicePackage::with(['items.category'])
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
            'image' => ['nullable', 'image', 'max:20480'], // max ~20MB
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

        // Build the update data
        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'total_price' => $totalPrice,
        ];

        // Handle "remove image" (from Remove Image button, sets hidden remove_image field to "1")
        if ($request->input('remove_image') == "1" && $package->image) {
            \Storage::disk('public')->delete($package->image);
            $updateData['image'] = null;
        }

        // Handle new image upload (replaces old image)
        if ($request->hasFile('image')) {
            // Delete old image if any
            if ($package->image) {
                \Storage::disk('public')->delete($package->image);
            }
            $updateData['image'] = $request->file('image')->store('service_packages', 'public');
        }

        $package->update($updateData);

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

