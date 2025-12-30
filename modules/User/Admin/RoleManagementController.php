<?php

namespace Modules\User\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
{
    // List all roles
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return response()->json($roles);
    }

    // Get single role with permissions
    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json($role);
    }

    // Create role
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    // Update role
    public function update($id, Request $request)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'display_name' => 'required|string',
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    // Bulk edit roles
    public function bulkEdit(Request $request)
    {
        $ids = $request->ids;
        $action = $request->action;

        if ($action === 'delete') {
            // Don't allow deleting admin role (id: 1)
            Role::whereIn('id', $ids)->where('id', '!=', 1)->delete();
        }

        return response()->json(['success' => true]);
    }

    // Get all permissions
    public function getPermissions()
    {
        $permissions = Permission::all()->groupBy('group');
        return response()->json($permissions);
    }

    // Assign permissions to role
    public function assignPermissions($roleId, Request $request)
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($request->permissions);

        return response()->json(['success' => true]);
    }
}
