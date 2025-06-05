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
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category = new InventoryCategory($validated);
        $category->funeral_home_id = auth()->id();
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
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->route('funeral.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(InventoryCategory $category)
    {
        if ($category->funeral_home_id !== auth()->id()) {
            abort(403);
        }
        $category->delete();

        return redirect()->route('funeral.categories.index')->with('success', 'Category deleted.');
    }
}
