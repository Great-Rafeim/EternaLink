<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    
    public function index(Request $request)
    {
        $user = auth()->user();
        $type = $request->query('type', 'all');
        $query = $user->notifications();

        if ($type === 'unread') {
            $query->whereNull('read_at');
        } elseif ($type === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);

        // Route based on role
        switch ($user->role) {
            case 'admin':
                $view = 'admin.notifications.index';
                break;
            case 'funeral':
                $view = 'funeral.notifications.index';
                break;
            case 'client':
                $view = 'client.notifications.index';
                break;
            case 'agent':
                $view = 'agent.notifications.index';
                break;
            case 'cemetery':
                $view = 'cemetery.notifications.index';
                break;
            default:
                $view = 'client.notifications.index';
                break;
        }

        return view($view, compact('notifications', 'type'));
    }




    // Mark a single notification as read
    public function markAsRead(Request $request, $id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    // Mark all unread notifications as read
    public function markAllAsRead(Request $request)
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    // Delete a notification
    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification deleted.']);
        }

        return back()->with('success', 'Notification deleted.');
    }

    // View a single notification (optional, direct view)
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);

        // Mark as read on view
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        // You can redirect to a related URL if present in data:
        if (!empty($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        // Or render a dedicated view
        return view('notifications.show', compact('notification'));
    }

    // Main "redirect and mark as read" for bell dropdown/quick links
    public function redirect($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        // Mark as read
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // Redirect to the stored URL, or fallback
        $url = $notification->data['url'] ?? route('notifications.index');
        return redirect($url);
    }
}
