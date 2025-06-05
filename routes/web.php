<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminUserManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\FuneralDashboardController;
use App\Http\Controllers\CemeteryDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PasswordChangeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\auth\AuthenticatedSessionController;
use App\Http\Controllers\PlotController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\FuneralNotificationController;
use App\Http\Controllers\InventoryCategoryController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\PartnershipController;





// 2FA setup and disable routes (accessible after login)
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::get('/2fa/disable', [TwoFactorController::class, 'showDisableForm'])->name('2fa.disable.form');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify.form');

    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
});

// Routes that require 2FA validation
Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'client' => redirect()->route('client.dashboard'),
            'funeral' => redirect()->route('funeral.dashboard'),
            'cemetery' => redirect()->route('cemetery.dashboard'),
            default => abort(403),
        };
    })->name('dashboard');
});



Route::get('/', function () {
    return view('welcome');
});




Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['auth', 'verified', 'role:client'])->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
});

Route::middleware(['auth', 'verified', 'role:funeral'])->group(function () {
    Route::get('/funeral/dashboard', [FuneralDashboardController::class, 'index'])->name('funeral.dashboard');
});

Route::middleware(['auth', 'verified', 'role:cemetery'])->group(function () {
    Route::get('/cemetery/dashboard', [CemeteryDashboardController::class, 'index'])->name('cemetery.dashboard');
});


// Profile routes (restricted to admin users)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes (restricted to admin users)
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/login-history', [AdminDashboardController::class, 'loginHistory'])->name('admin.login-history');

    // Admin user management routes
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/{role?}', [AdminUserManagementController::class, 'index'])->name('index');
        Route::get('create', [AdminUserManagementController::class, 'create'])->name('create');
        Route::post('store', [AdminUserManagementController::class, 'store'])->name('store');
        Route::get('{user}/edit', [AdminUserManagementController::class, 'edit'])->name('edit');
        Route::put('{user}', [AdminUserManagementController::class, 'update'])->name('update');
        Route::delete('{user}', [AdminUserManagementController::class, 'destroy'])->name('destroy');
        Route::post('{user}/reset-password', [AdminUserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::get('/admin/users/ajax-search', [AdminUserManagementController::class, 'ajaxSearch']);
        Route::get('export/csv', [AdminUserManagementController::class, 'exportCsv'])->name('export');
        Route::post('/admin/users/{id}/restore', [AdminUserManagementController::class, 'restore'])->name('admin.users.restore');
        Route::get('/force-password-change', [PasswordChangeController::class, 'showForm'])->name('password.change.form');
        Route::post('/force-password-change', [PasswordChangeController::class, 'update'])->name('password.change.update');
    });

    // Reset password confirmation form and action
    Route::get('admin/users/{user}/reset-password', [AdminUserManagementController::class, 'showResetPasswordForm'])->name('admin.users.reset-password.form');
    Route::post('admin/users/{user}/reset-password', [AdminUserManagementController::class, 'resetPassword'])->name('admin.users.reset-password');
});

// Funeral Routes
Route::prefix('funeral')->name('funeral.')->middleware(['auth', 'verified', 'role:funeral'])->group(function () {
    Route::resource('packages', PackageController::class);
    Route::resource('schedules', \App\Http\Controllers\ScheduleController::class)->names('funeral.schedules');
    Route::resource('clients', ClientController::class);
    Route::resource('staff', StaffController::class);
});

Route::middleware(['auth', 'verified', 'role:funeral'])->group(function () {
    Route::get('/funeral/packages/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('/funeral/packages', [PackageController::class, 'store'])->name('packages.store');

});


Route::prefix('funeral')
    ->middleware(['auth', 'verified', 'role:funeral'])
    ->name('funeral.')
    ->group(function () {

        Route::resource('schedules', ScheduleController::class);

        //packages
        Route::resource('packages', \App\Http\Controllers\PackageController::class);
        
        //partnerships
        Route::resource('partnerships', PartnershipController::class)->only(['index']);
        Route::get('partnerships/find', [PartnershipController::class, 'find'])->name('partnerships.find');
        Route::post('partnerships/request', [PartnershipController::class, 'sendRequest'])->name('partnerships.request');
        Route::delete('partnerships/{partnership}', [PartnershipController::class, 'destroy'])->name('partnerships.destroy');
        Route::post('partnerships/{partnership}/respond', [PartnershipController::class, 'respond'])->name('partnerships.respond');
        Route::delete('partnerships/{partnership}', [PartnershipController::class, 'destroy'])->name('partnerships.destroy');




        // Inventory Items
        Route::resource('items', InventoryItemController::class);
        Route::post('/items/{item}/adjust-stock', [InventoryItemController::class, 'adjustStock'])->name('items.adjustStock');
        Route::get('/items/{item}/movements', [InventoryItemController::class, 'movements'])->name('items.movements');
        Route::get('/items-export', [InventoryItemController::class, 'export'])->name('items.export');


        // Inventory Categories
        Route::resource('categories', InventoryCategoryController::class);

        // Notifications (inline handling)
        Route::post('/notifications/{id}/read', function ($id) {
            $notification = auth()->user()->notifications()->findOrFail($id);
            $notification->markAsRead();
            return back();
        })->name('notifications.read');

        Route::get('/notifications', function () {
            $user = auth()->user();
            return view('funeral.notifications.index', [
                'unread' => $user->unreadNotifications,
                'read' => $user->readNotifications()->latest()->take(20)->get(),
            ]);
        })->name('notifications.index');

        // Optional explicit controller route (if needed elsewhere)
        Route::get('/funeral/notifications', [FuneralNotificationController::class, 'index'])
            ->name('notifications.index.controller');
    });






//Cemetery Routes
Route::prefix('cemetery')->middleware(['auth', 'verified', 'role:cemetery'])->group(function () {
    Route::resource('plots', PlotController::class);
    Route::put('/plots/{plot}/update-reservation', [PlotController::class, 'updateReservation'])->name('plots.updateReservation');
    Route::put('/plots/{plot}/update-occupation', [PlotController::class, 'updateOccupation'])->name('plots.updateOccupation');
    Route::put('/plots/{plot}/mark-available', [PlotController::class, 'markAvailable'])->name('plots.markAvailable');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile/security', function () {
        return view('profile.security');
    })->name('profile.security');
});

require __DIR__.'/auth.php';

