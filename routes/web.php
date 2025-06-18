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
use App\Http\Controllers\ResourceRequestController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\FuneralParlorController;
use App\Http\Controllers\ClientBookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BookingDetailPreviewController;
use App\Http\Controllers\BookingContinueController;
use \App\Http\Controllers\BookingPackageCustomizationController;
use \App\Http\Controllers\AssetReservationControllerl;
use App\Http\Controllers\AssetReservationController;
use App\Http\Controllers\ResourceShareController;


Route::get('/', function () {
    return view('welcome');
});

// client routes
Route::prefix('client')
    ->middleware(['auth', 'verified', 'role:client'])
    ->name('client.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [ClientDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings/{booking}', [ClientDashboardController::class, 'show'])->name('bookings.show');
        Route::put('/bookings/{booking}/cancel', [ClientDashboardController::class, 'cancel'])->name('bookings.cancel');

        // Profile
        Route::get('/profile', [ClientController::class, 'profile'])->name('profile');
        Route::post('/profile', [ClientController::class, 'updateProfile'])->name('profile.update');

        // Funeral Parlors
        Route::get('/parlors', [ClientController::class, 'parlors'])->name('parlors.index');
        Route::get('/parlors/{id}/service-packages', [ClientController::class, 'showServicePackages'])->name('parlors.service_packages');

        // Booking
        Route::get('/parlors/packages/{package}/book', [ClientBookingController::class, 'showBookForm'])->name('parlors.packages.book');
        Route::post('/parlors/packages/{package}/book', [ClientBookingController::class, 'store'])->name('parlors.packages.book.submit');

        // Booking Phase 2 (Details, Payment, Agent)
        Route::get('/bookings/{booking}/continue', [BookingContinueController::class, 'edit'])->name('bookings.continue.edit');
        Route::post('/bookings/{booking}/continue', [BookingContinueController::class, 'update'])->name('bookings.continue.update');

        // Package Customization
        Route::get('/bookings/{booking}/customize-package', [BookingPackageCustomizationController::class, 'edit'])->name('bookings.package_customization.edit');
        Route::post('/bookings/{booking}/customize-package', [BookingPackageCustomizationController::class, 'update'])->name('bookings.package_customization.update');
        Route::post('/bookings/{booking}/customize-package/send', [BookingPackageCustomizationController::class, 'sendRequest'])->name('bookings.package_customization.send');

        // Booking Details
        Route::get('/bookings/{booking}/details', [BookingDetailPreviewController::class, 'show'])->name('bookings.details.show');
        Route::get('/bookings/{booking}/details/pdf', [BookingDetailPreviewController::class, 'exportPdf'])->name('bookings.details.exportPdf');

        Route::get('/bookings/{booking}/next-step', [BookingContinueController::class, 'nextStep'])->name('client.bookings.next_step');

        // Phase 3: Info of the Dead form
        Route::get('/bookings/{booking}/info', [BookingContinueController::class, 'info'])->name('bookings.continue.info');

        // Phase 3: Save/Update info-of-the-dead
        Route::post('/bookings/{booking}/info', [BookingContinueController::class, 'saveInfo'])->name('bookings.continue.info.save');
        Route::post('/bookings/continue/{booking}/info', [BookingContinueController::class, 'updateInfo'])->name('bookings.details.update');


    });






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

Route::middleware(['auth', '2fa', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['auth', '2fa', 'verified', 'role:client'])->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
});

Route::middleware(['auth', '2fa', 'verified', 'role:funeral'])->group(function () {
    Route::get('/funeral/dashboard', [FuneralDashboardController::class, 'index'])->name('funeral.dashboard');
});

Route::middleware(['auth', '2fa', 'verified', 'role:cemetery'])->group(function () {
    Route::get('/cemetery/dashboard', [CemeteryDashboardController::class, 'index'])->name('cemetery.dashboard');
});


// Profile routes (restricted to admin users)
Route::middleware(['auth', '2fa', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes (restricted to admin users)
Route::middleware(['auth', 'verified', '2fa', 'role:admin'])->group(function () {
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
Route::prefix('funeral')->name('funeral.')->middleware(['auth', 'verified', '2fa', 'role:funeral'])->group(function () {
    Route::resource('packages', PackageController::class);
    Route::resource('schedules', \App\Http\Controllers\ScheduleController::class)->names('funeral.schedules');
    Route::resource('clients', ClientController::class);
    Route::resource('staff', StaffController::class);
});

Route::middleware(['auth', 'verified', '2fa', 'role:funeral'])->group(function () {
    Route::get('/funeral/packages/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('/funeral/packages', [PackageController::class, 'store'])->name('packages.store');

});


Route::prefix('funeral')
    ->middleware(['auth', 'verified', 'role:funeral'])
    ->name('funeral.')
    ->group(function () {

        Route::resource('schedules', ScheduleController::class);

        //funeral profile
        Route::get('profile/edit', [FuneralParlorController::class, 'editProfile'])->name('profile.edit');
        Route::post('profile/edit', [FuneralParlorController::class, 'updateProfile'])->name('profile.update');

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

        // Resource Sharing
        Route::get('/funeral/resource-requests/request/{id}', [ResourceShareController::class, 'showShareableItems'])->name('partnerships.resource_requests.request');
        Route::get('resource-requests/request', [ResourceShareController::class, 'showShareableItems'])->name('partnerships.resource_requests.request');
        Route::post('/funeral/resource-requests/send-request/{item}/{shareable}', [ResourceShareController::class, 'sendRequest'])->name('partnerships.resource_requests.sendRequest');
        Route::get('/funeral/resource-requests/request/{requested}/{provider}', [ResourceShareController::class, 'createRequestForm'])->name('partnerships.resource_requests.createRequestForm');
        Route::post('/funeral/resource-requestss/request', [ResourceShareController::class, 'storeRequest'])->name('partnerships.resource_requests.storeRequest');
        Route::get('/funeral/partnerships/resource-requests/request', [ResourceShareController::class, 'searchPage'])->name('partnerships.resource_requests.browse');

        Route::get('/funeral/resource-requests', [ResourceRequestController::class, 'index'])->name('partnerships.resource_requests.index');

        Route::get('resource-requests', [ResourceRequestController::class, 'index'])->name('partnerships.resource_requests.index');
        Route::get('resource-requests/{id}', [ResourceRequestController::class, 'show'])->name('partnerships.resource_requests.show');
        Route::patch('resource-requests/{id}/cancel', [ResourceRequestController::class, 'cancel'])->name('partnerships.resource_requests.cancel');
        Route::patch('resource-requests/{id}/approve', [ResourceRequestController::class, 'approve'])->name('partnerships.resource_requests.approve');
        Route::patch('resource-requests/{id}/reject', [ResourceRequestController::class, 'reject'])->name('partnerships.resource_requests.reject');
        Route::patch('resource-requests/{id}/cancel', [ResourceRequestController::class, 'cancel'])->name('partnerships.resource_requests.cancel');
        Route::patch('resource-requests/{id}/fulfill', [ResourceRequestController::class, 'fulfill'])->name('partnerships.resource_requests.fulfill');

        //Bookings
        Route::get('/bookings', [FuneralDashboardController::class, 'bookings'])->name('bookings.index');
        Route::get('/bookings/{booking}', [FuneralDashboardController::class, 'show'])->name('bookings.show');
        Route::get('/bookings/{booking}', [FuneralDashboardController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}/approve', [FuneralDashboardController::class, 'approve'])->name('bookings.approve');
        Route::patch('/bookings/{booking}/deny', [FuneralDashboardController::class, 'deny'])->name('bookings.deny');
        Route::patch('/bookings/{booking}/accept', [FuneralDashboardController::class, 'accept'])->name('bookings.accept');
        Route::patch('/bookings/{booking}/reject', [FuneralDashboardController::class, 'reject'])->name('bookings.reject');
        Route::get('/bookings/{booking}/customization/{customizedPackage}', [FuneralDashboardController::class, 'customizationShow'])->name('bookings.customization.show');
        Route::post('/bookings/{booking}/customization/{customizedPackage}/approve', [FuneralDashboardController::class, 'customizationApprove'])->name('bookings.customization.approve');
        Route::post('/bookings/{booking}/customization/{customizedPackage}/deny', [FuneralDashboardController::class, 'customizationDeny'])->name('bookings.customization.deny');
        Route::patch('/bookings/{booking}/details', [FuneralDashboardController::class, 'updateDetails'])->name('bookings.details.update');
        Route::get('/bookings/{booking}/review-details', [FuneralDashboardController::class, 'reviewDetails'])->name('bookings.review.details');
        Route::patch('/bookings/{booking}/other-fees', [FuneralDashboardController::class, 'updateOtherFees'])->name('bookings.updateOtherFees');
        Route::patch('/bookings/{booking}/update-payment-remarks', [FuneralDashboardController::class, 'updatePaymentRemarks'])->name('bookings.updatePaymentRemarks');

        Route::get('/', [AssetReservationController::class, 'index'])->name('assets.reservations.index');
        Route::patch('/{reservation}/status', [AssetReservationController::class, 'updateStatus'])->name('assets.reservations.updateStatus');



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

//funeral-agent routes
Route::middleware(['auth', 'role:funeral'])->prefix('funeral')->group(function () {
    Route::get('/agents', [AgentController::class, 'index'])->name('funeral.agents.index');
    Route::post('/agents/invite', [AgentController::class, 'invite'])->name('funeral.agents.invite');
    Route::get('/agents/{agent}/edit', [AgentController::class, 'edit'])->name('funeral.agents.edit');
    Route::post('/agents/{agent}/edit', [AgentController::class, 'update'])->name('funeral.agents.update');
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('funeral.agents.destroy');


    
});

Route::match(['get', 'post'], '/agents/accept-invite/{invite}', [AgentController::class, 'acceptInvite'])->name('agents.accept-invite');



//Cemetery Routes
Route::prefix('cemetery')->middleware(['auth', 'verified', 'role:cemetery'])->group(function () {
    Route::resource('plots', PlotController::class);
    Route::put('/plots/{plot}/update-reservation', [PlotController::class, 'updateReservation'])->name('plots.updateReservation');
    Route::put('/plots/{plot}/update-occupation', [PlotController::class, 'updateOccupation'])->name('plots.updateOccupation');
    Route::put('/plots/{plot}/mark-available', [PlotController::class, 'markAvailable'])->name('plots.markAvailable');
});



// Notification:
Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/redirect', [NotificationController::class, 'redirect'])->name('notifications.redirect');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');



});




require __DIR__.'/auth.php';

