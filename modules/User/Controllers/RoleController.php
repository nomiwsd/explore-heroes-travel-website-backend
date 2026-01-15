<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Models\Role;
use Modules\User\Models\RolePermission;
use Modules\User\Helpers\PermissionHelper;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query()->with(['permissions']);
        
        if ($request->has('s')) {
            $query->where('name', 'LIKE', '%' . $request->s . '%');
        }

        $roles = $query->withCount('users')->paginate(20);
        
        // Transform permissions to array of strings for consistency
        $roles->getCollection()->transform(function ($role) {
            $role->permissions = $role->permissions->pluck('permission');
            return $role;
        });

        return response()->json([
            'data' => $roles->items(),
            'total' => $roles->total()
        ]);
    }

    public function store(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:core_roles,name,' . $id,
        ]);

        if ($id) {
            $role = Role::findOrFail($id);
        } else {
            $role = new Role();
        }

        $role->name = $request->name;
        $role->display_name = $request->display_name ?? Str::title(str_replace('-', ' ', $request->name));
        $role->description = $request->description;
        $role->code = $request->code ?? Str::slug($request->name, '_');
        $role->save();

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load(['permissions']);
        // Transform permissions to simple array
        $role->setAttribute('permissions', $role->permissions->pluck('permission'));

        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        // Transform for frontend
        $roleData = $role->toArray();
        $roleData['permissions'] = $role->permissions->pluck('permission')->toArray();

        return response()->json(['data' => $roleData]);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['error' => 'No items selected'], 400);
        }

        if ($action === 'delete') {
            // Prevent deleting system roles
            $systemRoles = [4, 5]; // Only protect Admin and Super Admin
            $safeIds = array_diff($ids, $systemRoles);
            
            if (count($safeIds) !== count($ids)) {
                 return response()->json(['error' => 'Cannot delete system roles'], 400);
            }

            Role::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Action not supported'], 400);
    }

    public function getPermissions()
    {
        $permissions = config('permissions');
        $grouped = [];
        
        if (is_array($permissions)) {
            foreach($permissions as $group => $perms) {
                $grouped[$group] = [];
                if (is_array($perms)) {
                    foreach($perms as $perm) {
                        $grouped[$group][] = [
                            'name' => $perm,
                            'display_name' => ucwords(str_replace(['_', '-'], ' ', $perm))
                        ];
                    }
                }
            }
        }

        return response()->json(['data' => $grouped]);
    }
}
