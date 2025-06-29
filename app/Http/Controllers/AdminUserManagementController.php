<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Notifications\UserApproved;
use App\Notifications\UserRejected;



class AdminUserManagementController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,client,funeral,cemetery',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

public function index(Request $request)
{
    $query = User::query();

    if ($request->filled('role')) {
        $query->where('role', $request->role);
    }
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%');
        });
    }
    $users = $query->orderBy('created_at', 'desc')->paginate(10);

    $pendingRequests = User::where('status', 'pending')->latest()->get();

    return view('admin.users.index', [
        'users' => $users,
        'pendingRequests' => $pendingRequests,
        'role' => $request->role ?? '',
        'status' => $request->status ?? '',
    ]);
}






    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:client,funeral,cemetery',
        ]);

        $user->update($validated);

        return redirect()->route('admin.dashboard')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User soft deleted successfully.');
    }


    public function resetPassword(User $user)
    {
        $newPassword = 'password123'; // or generate with Str::random(10)

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.dashboard')->with('success', "Password reset. New password: '{$newPassword}'. User will be required to change it on next login.");
    }


    public function showResetPasswordForm(User $user)
    {
        return view('admin.users.reset-password-confirm', compact('user'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function ajaxSearch(Request $request)
    {
        $query = User::query();

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->limit(20)->get();

        return view('admin.users.partials.table-rows', compact('users'));
    }

    public function exportCsv(Request $request)
    {
        $users = User::all();

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=users.csv",
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Role', 'Created At']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->created_at,
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.index')->with('success', 'User restored successfully.');
    }

public function approve(User $user)
{
    $user->status = 'approved';
    $user->save();

    $user->notify(new \App\Notifications\UserApproved());

    return back()->with('success', 'User approved.');
}

public function reject(User $user)
{
    $user->status = 'rejected';
    $user->save();

    $user->notify(new \App\Notifications\UserRejected());

    return back()->with('success', 'User rejected.');
}
public function show(User $user)
{
    // If you want to load extra relations, do it here.
    // Example: $user->load('profile');
    return view('admin.users.show', compact('user'));
}

}
