<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    public function index()
    {
        $categories = InventoryCategory::where('funeral_home_id', auth()->id())
            ->orderBy('name')->paginate(10);

        return view('funeral.categories.index', compact('categories'));
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
            'reservation_mode'=> $isAsset ? ($validated['reservation_mode'] ?? 'continuous') : null,
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

        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'is_asset'         => 'sometimes|boolean',
            'reservation_mode' => 'required_if:is_asset,1|in:continuous,single_event|nullable',
            'image'            => 'nullable|image|max:20480',
        ]);

        $isAsset = $request->has('is_asset');
        $data = [
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'is_asset'        => $isAsset,
            'reservation_mode'=> $isAsset ? ($validated['reservation_mode'] ?? 'continuous') : null,
        ];

        // Handle remove image
        if ($isAsset && $request->input('remove_image') == "1" && $category->image) {
            \Storage::disk('public')->delete($category->image);
            $data['image'] = null;
        }

        // Handle new image upload
        if ($isAsset && $request->hasFile('image')) {
            if ($category->image) {
                \Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('category_images', 'public');
        }

        // If switched to non-asset, remove image
        if (!$isAsset && $category->image) {
            \Storage::disk('public')->delete($category->image);
            $data['image'] = null;
        }

        $category->update($data);

        return redirect()->route('funeral.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(InventoryCategory $category)
    {
        if ($category->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        // Remove image if exists
        if ($category->image) {
            \Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        return redirect()->route('funeral.categories.index')->with('success', 'Category deleted.');
    }
}
