<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
private function categoriesQueryAndView(Request $request, $ajax = false)
{
    $query = \App\Models\InventoryCategory::where('funeral_home_id', auth()->id());

    if ($request->filled('type') && in_array($request->input('type'), ['asset', 'consumable'])) {
        $query->where('is_asset', $request->input('type') === 'asset');
    }

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    $sortable = ['name', 'description', 'is_asset', 'reservation_mode'];
    $sort = $request->input('sort', 'name');
    $direction = $request->input('direction', 'asc');
    if (in_array($sort, $sortable)) {
        $query->orderBy($sort, $direction);
    } else {
        $query->orderBy('name', 'asc');
    }

    $categories = $query->paginate(10)->withQueryString();

    if ($ajax) {
        $html = view('funeral.categories.index', [
            'categories' => $categories,
            'search' => $request->input('search', ''),
            'type' => $request->input('type', ''),
            'sort' => $sort,
            'direction' => $direction,
            'ajaxTableOnly' => true
        ])->render();
        return response()->json(['html' => $html]);
    }

    return view('funeral.categories.index', [
        'categories' => $categories,
        'search' => $request->input('search', ''),
        'type' => $request->input('type', ''),
        'sort' => $sort,
        'direction' => $direction,
        'ajaxTableOnly' => false
    ]);
}

public function index(Request $request)
{
    $query = InventoryCategory::where('funeral_home_id', auth()->id());

    // Filtering
    if ($request->filled('type') && in_array($request->input('type'), ['asset', 'consumable'])) {
        $query->where('is_asset', $request->input('type') === 'asset');
    }

    // Searching
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Sorting
    $sortable = ['name', 'description', 'is_asset', 'reservation_mode'];
    $sort = $request->input('sort', 'name');
    $direction = $request->input('direction', 'asc');
    if (in_array($sort, $sortable)) {
        $query->orderBy($sort, $direction);
    } else {
        $query->orderBy('name', 'asc');
    }

    $categories = $query->paginate(10)->withQueryString();

    return view('funeral.categories.index', [
        'categories' => $categories,
        'search' => $request->input('search', ''),
        'type' => $request->input('type', ''),
        'sort' => $sort,
        'direction' => $direction,
    ]);
}

    public function create()
    {
        return view('funeral.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'is_asset'         => 'sometimes|boolean',
            'reservation_mode' => 'required_if:is_asset,1|in:continuous,single_event|nullable',
            'image'            => 'nullable|image|max:20480',
        ]);

        $isAsset = $request->has('is_asset');

        $category = new InventoryCategory([
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'is_asset'        => $isAsset,
            'reservation_mode'=> $isAsset ? ($validated['reservation_mode'] ?? 'continuous') : 'continuous',
            'funeral_home_id' => auth()->id(),
        ]);

        // Handle image upload (only if asset and has file)
        if ($isAsset && $request->hasFile('image')) {
            $category->image = $request->file('image')->store('category_images', 'public');
        }

        $category->save();

        return redirect()->route('funeral.categories.index')->with('success', 'Category added.');
    }

    public function edit(InventoryCategory $category)
    {
        if ($category->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        return view('funeral.categories.edit', compact('category'));
    }

public function update(Request $request, InventoryCategory $category)
{
    if ($category->funeral_home_id !== auth()->id()) {
        abort(403);
    }

    // Notice we don't require reservation_mode anymore, just allow nullable
    $validated = $request->validate([
        'name'             => 'required|string|max:100',
        'description'      => 'nullable|string',
        'is_asset'         => 'sometimes|boolean',
        'reservation_mode' => 'nullable|in:continuous,single_event',
        'image'            => 'nullable|image|max:20480',
    ]);

    $isAsset = $request->has('is_asset');

    $data = [
        'name'            => $validated['name'],
        'description'     => $validated['description'] ?? null,
        'is_asset'        => $isAsset,
        // Only set reservation_mode if asset, else set to null
        'reservation_mode'=> $isAsset ? ($validated['reservation_mode'] ?? null) : null,
    ];

    // Remove image if needed
    if ($isAsset && $request->input('remove_image') == "1" && $category->image) {
        \Storage::disk('public')->delete($category->image);
        $data['image'] = null;
    }

    // New image upload
    if ($isAsset && $request->hasFile('image')) {
        if ($category->image) {
            \Storage::disk('public')->delete($category->image);
        }
        $data['image'] = $request->file('image')->store('category_images', 'public');
    }

    // If switched to non-asset, always remove image and reservation_mode
    if (!$isAsset) {
        if ($category->image) {
            \Storage::disk('public')->delete($category->image);
        }
        $data['image'] = null;
        $data['reservation_mode'] = null;
    }

    $category->update($data);

    return redirect()->route('funeral.categories.index')->with('success', 'Category updated.');
}


public function destroy(InventoryCategory $category)
{
    if ($category->funeral_home_id !== auth()->id()) {
        abort(403);
    }

    // Set inventory_category_id to null for all items in this category
    \App\Models\InventoryItem::where('inventory_category_id', $category->id)
        ->update(['inventory_category_id' => null]);

    // Remove image if exists
    if ($category->image) {
        \Storage::disk('public')->delete($category->image);
    }

    $category->delete();

    return redirect()->route('funeral.categories.index')->with('success', 'Category deleted.');
}

}
