<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\ServicePackage;

class FuneralDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        return view('funeral.dashboard', [
            // Only items owned by this user
            'totalItems' => InventoryItem::where('funeral_home_id', $userId)->count(),

            // Only low stock items for this user
            'lowStockCount' => InventoryItem::where('funeral_home_id', $userId)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->count(),

            // Only categories for this user
            'categoryCount' => InventoryCategory::where('funeral_home_id', $userId)->count(),

            // Only packages for this user (if ServicePackage has funeral_home_id)
            'packageCount' => ServicePackage::where('funeral_home_id', $userId)->count(),

            // Notifications for this user
            'recentNotifications' => $user->notifications()->latest()->paginate(5),
        ]);
    }
}
