<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\AuditLog;
use Modules\User\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        // Show trashed
        if ($request->has('trashed') && $request->trashed == 'true') {
             $query->onlyTrashed();
        }

        if ($request->has('s') && $request->s) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->s . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->s . '%');
            });
        }
        
        if ($request->has('role') && $request->role && $request->role !== 'all') {
             if (is_numeric($request->role)) {
                 $query->where('role_id', $request->role);
             } else {
                 $role = Role::where('name', $request->role)->orWhere('code', $request->role)->first();
                 if ($role) $query->where('role_id', $role->id);
             }
        }
        
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $status = $request->status;
            if ($status == 'active') $status = 'publish';
            if ($status == 'inactive') $status = 'blocked';
            $query->where('status', $status);
        }

        $users = $query->with('role')->orderBy('id', 'desc')->paginate($request->input('limit', 20));

        // Transform
        $data = $users->map(function ($user) {
            return [
                 'id' => $user->id,
                 'name' => $user->name,
                 'email' => $user->email,
                 'phone' => $user->phone, // Added
                 'bio' => $user->bio, // Added
                 'role_id' => $user->role_id,
                 'role_name' => $user->role->name ?? $user->role_name ?? 'User',
                 'status' => $user->status === 'publish' ? 'active' : ($user->status === 'blocked' ? 'inactive' : $user->status),
                 'created_at' => $user->created_at,
                 'avatar_url' => $user->avatar_url,
                 'last_login_at' => $user->last_login_at,
                 'deleted_at' => $user->deleted_at // For soft delete check
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage()
        ]);
    }

    public function show($id)
    {
        $user = User::withTrashed()->with(['role'])->findOrFail($id);
        
        $data = [
             'id' => $user->id,
             'name' => $user->name,
             'email' => $user->email,
             'phone' => $user->phone,
             'bio' => $user->bio,
             'role_id' => $user->role_id,
             'role_name' => $user->role->name ?? 'User',
             'status' => $user->status === 'publish' ? 'active' : ($user->status === 'blocked' ? 'inactive' : $user->status),
             'created_at' => $user->created_at,
             'avatar_url' => $user->avatar_url,
             'last_login_at' => $user->last_login_at,
             'deleted_at' => $user->deleted_at
        ];

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, $id = null)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $id,
            'name' => 'required|string',
            'role_id' => 'required|exists:core_roles,id'
        ]);

        if ($id) {
            $user = User::findOrFail($id);
            $action = 'update';
            $oldValues = $user->toArray();
        } else {
            $user = new User();
            $action = 'create';
            $oldValues = null;
            $request->validate(['password' => 'required|min:6']);
        }

        $data = $request->only([
            'name', 'first_name', 'last_name', 'email', 'phone', 
            'bio', 'address', 'city', 'country', 'zip_code', 
            'role_id', 'force_password_change', 'avatar_id'
        ]);
        
        // Status mapping
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status == 'active') $status = 'publish';
            if ($status == 'inactive') $status = 'blocked';
            $data['status'] = $status;
        }

        $user->fill($data);
        
        if ($request->has('responsibility')) {
            $user->addMeta('responsibility', $request->responsibility);
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        
        // Audit Log
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $action,
            'auditable_type' => 'User',
            'auditable_id' => $user->id,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => json_encode($user->toArray()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user->load('role');
        $user->role_name = $user->role ? $user->role->name : '';
        
        return response()->json(['success' => true, 'data' => $user]);
    }


    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');
        
        if (empty($ids)) return response()->json(['error' => 'No items selected'], 400);

        if (in_array(Auth::id(), $ids)) {
             return response()->json(['error' => 'Cannot perform action on yourself'], 400);
        }
        
        $superAdminRole = 1; 
        $targets = User::withTrashed()->whereIn('id', $ids)->get(); // Include trashed for restore/forceDelete
        
        foreach ($targets as $target) {
            if ($target->role_id == $superAdminRole && Auth::user()->role_id != $superAdminRole) {
                return response()->json(['error' => 'Cannot modify Super Admin'], 403);
            }
        }

        switch ($action) {
            case 'delete':
                foreach($targets as $target) {
                    $target->forceDelete(); // Permanent delete as requested
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'event' => 'permanent_delete',
                        'auditable_type' => 'User',
                        'auditable_id' => $target->id,
                        'ip_address' => $request->ip()
                    ]);
                }
                break;
            case 'restore':
                foreach($targets as $target) {
                    $target->restore();
                    AuditLog::create([ // Log restore
                        'user_id' => Auth::id(),
                        'event' => 'restore',
                        'auditable_type' => 'User',
                        'auditable_id' => $target->id,
                        'ip_address' => $request->ip()
                    ]);
                }
                break;
            case 'permanent_delete':
                foreach($targets as $target) {
                    $target->forceDelete();
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'event' => 'force_delete',
                        'auditable_type' => 'User',
                        'auditable_id' => $target->id,
                        'ip_address' => $request->ip()
                    ]);
                }
                break;
            case 'activate':
                User::whereIn('id', $ids)->update(['status' => 'publish']);
                break;
            case 'deactivate':
                User::whereIn('id', $ids)->update(['status' => 'blocked']);
                break;
        }

        return response()->json(['success' => true]);
    }

    public function passwordSecurity(Request $request, $id)
    {
         $user = User::findOrFail($id);
         $action = $request->input('action'); // 'logout_all' or 'force_change'
         
         if ($action == 'force_change') {
             $user->force_password_change = true;
             $user->save();
         } elseif ($action == 'logout_all') {
             // Revoke all tokens
             $user->tokens()->delete();
         }
         
         AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'security_' . $action,
            'auditable_type' => 'User',
            'auditable_id' => $user->id,
            'ip_address' => $request->ip()
        ]);
        
        return response()->json(['success' => true]);
    }
}
