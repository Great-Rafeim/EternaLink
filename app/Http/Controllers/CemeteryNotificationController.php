<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CemeteryNotificationController extends Controller
{
    /**
     * Show a list of notifications for the cemetery user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Ensure user is cemetery role
        if ($user->role !== 'cemetery') {
            abort(403, 'Unauthorized');
        }

        // Optionally paginate
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('cemetery.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead($notificationId)
    {
        $user = auth()->user();

        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     */
    public function destroy($notificationId)
    {
        $user = auth()->user();

        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }
}
