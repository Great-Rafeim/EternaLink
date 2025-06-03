<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\PackageCategory;
use App\Models\CategoryItem;
use Auth;

class PackageController extends Controller
{

    public function index()
    {
        $packages = \App\Models\ServicePackage::with('categories.items')
            ->where('funeral_home_id', auth()->id())
            ->get();

        return view('funeral.packages.index', compact('packages'));
    }



    public function create()
    {
        return view('funeral.packages.create');
    }

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'categories' => 'required|array',
        'categories.*.name' => 'required|string|max:255',
        'categories.*.items' => 'required|array',
        'categories.*.items.*.name' => 'required|string|max:255',
        'categories.*.items.*.quantity' => 'required|integer|min:1',
        'categories.*.items.*.price' => 'required|numeric|min:0',
        'categories.*.items.*.description' => 'nullable|string',
    ]);

    $total = 0;

    // Calculate total
    foreach ($request->categories as $category) {
        foreach ($category['items'] as $item) {
            $total += $item['quantity'] * $item['price'];
        }
    }

    // Create package
    $package = ServicePackage::create([
        'funeral_home_id' => Auth::id(),
        'name' => $request->name,
        'description' => $request->description,
        'total_price' => $total,
    ]);

    // Create categories and items
    foreach ($request->categories as $categoryData) {
        $category = PackageCategory::create([
            'service_package_id' => $package->id,
            'name' => $categoryData['name'],
        ]);

        foreach ($categoryData['items'] as $itemData) {
            CategoryItem::create([
                'package_category_id' => $category->id,
                'name' => $itemData['name'],
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'description' => $itemData['description'] ?? null,
            ]);
        }
    }

    return redirect()->route('packages.create')->with('success', 'Package created successfully!');
}

public function destroy($id)
{
    // Find the service package by ID or fail with 404
    $package = ServicePackage::findOrFail($id);

    // Optional: Add authorization check here to ensure the user owns this package
    if ($package->funeral_home_id !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }

    // Delete the package — cascades to categories and items via DB constraints
    $package->delete();

    // Redirect back with a success message
    return redirect()->route('funeral.packages.index')->with('success', 'Package deleted successfully.');
}


public function edit($id)
{
    $package = ServicePackage::with('categories.items')->findOrFail($id);

    if ($package->funeral_home_id !== auth()->id()) {
        abort(403);
    }

    return view('funeral.packages.edit', [
        'package' => $package,
    ]);

}



public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'categories' => 'required|array',
        'categories.*.name' => 'required|string|max:255',
        'categories.*.items' => 'required|array',
        'categories.*.items.*.name' => 'required|string|max:255',
        'categories.*.items.*.quantity' => 'required|integer|min:1',
        'categories.*.items.*.price' => 'required|numeric|min:0',
        'categories.*.items.*.description' => 'nullable|string',
    ]);

    $package = ServicePackage::with('categories.items')->findOrFail($id);

    if ($package->funeral_home_id !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }

    // Calculate new total price
    $total = 0;
    foreach ($request->categories as $category) {
        foreach ($category['items'] as $item) {
            $total += $item['quantity'] * $item['price'];
        }
    }

    // Update package
    $package->update([
        'name' => $request->name,
        'description' => $request->description,
        'total_price' => $total,
    ]);

    // Delete old categories and their items
    foreach ($package->categories as $category) {
        $category->items()->delete();
    }
    $package->categories()->delete();

    // Recreate categories and items from request
    foreach ($request->categories as $categoryData) {
        $category = PackageCategory::create([
            'service_package_id' => $package->id,
            'name' => $categoryData['name'],
        ]);

        foreach ($categoryData['items'] as $itemData) {
            CategoryItem::create([
                'package_category_id' => $category->id,
                'name' => $itemData['name'],
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'description' => $itemData['description'] ?? null,
            ]);
        }
    }

    return redirect()->route('funeral.packages.index')->with('success', 'Package updated successfully!');
}


}
