<?php

namespace Modules\User\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\Role;

class UserManagementController extends Controller
{
    // List all users
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role !== 'all' && $request->role !== '') {
            $query->where('role_id', $request->role);
        }

        if ($request->has('status') && $request->status !== 'all' && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json($users);
    }

    // Get single user
    public function edit($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json($user);
    }

    // Create user
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($user->load('role'));
    }

    // Update user
    public function update($id, Request $request)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role_id' => 'required|exists:roles,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'status' => $request->status ?? 'active',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user->load('role'));
    }

    // Bulk edit users
    public function bulkEdit(Request $request)
    {
        $ids = $request->ids;
        $action = $request->action;

        if ($action === 'delete') {
            User::whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            User::whereIn('id', $ids)->update(['status' => 'active']);
        } elseif ($action === 'deactivate') {
            User::whereIn('id', $ids)->update(['status' => 'inactive']);
        }

        return response()->json(['success' => true]);
    }
}
