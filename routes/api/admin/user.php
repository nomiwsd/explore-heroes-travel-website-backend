<?php

/**
 * ADMIN USER MODULE ROUTES
 * Supports both /module/user/* and /module/user/users/* patterns for frontend compatibility
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\User;
use Illuminate\Support\Facades\Hash;

// =====================================================
// USER MANAGEMENT
// =====================================================
Route::prefix('module/user')->middleware('auth:sanctum')->group(function () {
    
    // ========================
    // GET ALL USERS
    // ========================
    $getUsersList = function (Request $request) {
        try {
            $query = User::query();
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->s . '%')
                      ->orWhere('email', 'LIKE', '%' . $request->s . '%');
                });
            }
            
            // Also support 'search' parameter
            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('email', 'LIKE', '%' . $request->search . '%');
                });
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Role filter
            if ($request->has('role') && $request->role) {
                if ($request->role === 'admin') {
                    $query->whereIn('role_id', [1, 2]);
                } elseif ($request->role === 'user') {
                    $query->where('role_id', 3);
                }
            }
            
            $users = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'avatar_id' => $user->avatar_id,
                        'role_id' => $user->role_id,
                        'role_name' => $user->role_id == 1 ? 'Super Admin' : ($user->role_id == 2 ? 'Admin' : 'User'),
                        'status' => $user->status,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                    ];
                }),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    };
    
    Route::get('/', $getUsersList);
    Route::get('/users', $getUsersList);
    
    // ========================
    // GET SINGLE USER
    // ========================
    $getUser = function ($id) {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_id' => $user->avatar_id,
                    'role_id' => $user->role_id,
                    'status' => $user->status,
                    'bio' => $user->bio,
                    'address' => $user->address,
                    'city' => $user->city,
                    'country' => $user->country,
                    'zip_code' => $user->zip_code,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    };
    
    Route::get('/edit/{id}', $getUser);
    Route::get('/users/{id}', $getUser);
    
    // ========================
    // STORE/UPDATE USER
    // ========================
    $storeUser = function (Request $request, $id = null) {
        try {
            if ($id) {
                $user = User::findOrFail($id);
            } else {
                // Check if email already exists
                if (User::where('email', $request->input('email'))->exists()) {
                    return response()->json(['error' => 'Email already exists'], 400);
                }
                $user = new User();
            }
            
            $user->name = $request->input('name');
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->email = $request->input('email');
            $user->phone = $request->input('phone');
            $user->avatar_id = $request->input('avatar_id');
            $user->role_id = $request->input('role_id', 3);
            $user->status = $request->input('status', 'publish');
            $user->bio = $request->input('bio');
            $user->address = $request->input('address');
            $user->city = $request->input('city');
            $user->country = $request->input('country');
            $user->zip_code = $request->input('zip_code');
            
            // Update password only if provided
            if ($request->has('password') && $request->input('password')) {
                $user->password = Hash::make($request->input('password'));
            }
            
            $user->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $user->id],
                'message' => 'User saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    };
    
    Route::post('/store/{id?}', $storeUser);
    Route::post('/users/store', $storeUser);
    Route::post('/users/store/{id}', $storeUser);
    
    // ========================
    // DELETE USER
    // ========================
    Route::delete('/{id}', function ($id) {
        try {
            $user = User::findOrFail($id);
            
            // Don't allow deleting yourself
            if ($user->id === auth()->id()) {
                return response()->json(['error' => 'Cannot delete your own account'], 400);
            }
            
            // Don't allow deleting super admin
            if ($user->role_id == 1) {
                return response()->json(['error' => 'Cannot delete super admin'], 400);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // ========================
    // BULK EDIT
    // ========================
    $bulkEdit = function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            // Don't allow actions on current user
            $ids = array_diff($ids, [auth()->id()]);
            
            // Don't allow actions on super admin
            $superAdmins = User::whereIn('id', $ids)->where('role_id', 1)->pluck('id')->toArray();
            $ids = array_diff($ids, $superAdmins);
            
            if (empty($ids)) {
                return response()->json(['error' => 'No valid users to update'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    User::whereIn('id', $ids)->delete();
                    break;
                case 'activate':
                    User::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'deactivate':
                    User::whereIn('id', $ids)->update(['status' => 'blocked']);
                    break;
                default:
                    return response()->json(['error' => 'Invalid action'], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($action) . ' completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    };
    
    Route::post('/bulkEdit', $bulkEdit);
    Route::post('/users/bulkEdit', $bulkEdit);
    
    // ========================
    // ROLES
    // ========================
    Route::get('/roles', function () {
        try {
            return response()->json([
                'data' => [
                    ['id' => 1, 'name' => 'administrator', 'display_name' => 'Super Admin', 'users_count' => User::where('role_id', 1)->count()],
                    ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'users_count' => User::where('role_id', 2)->count()],
                    ['id' => 3, 'name' => 'user', 'display_name' => 'User', 'users_count' => User::where('role_id', 3)->count()],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
    
    Route::get('/roles/{id}', function ($id) {
        try {
            $roles = [
                1 => ['id' => 1, 'name' => 'administrator', 'display_name' => 'Super Admin', 'permissions' => []],
                2 => ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'permissions' => []],
                3 => ['id' => 3, 'name' => 'user', 'display_name' => 'User', 'permissions' => []],
            ];
            
            if (!isset($roles[$id])) {
                return response()->json(['error' => 'Role not found'], 404);
            }
            
            return response()->json($roles[$id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::post('/roles/store/{id?}', function (Request $request, $id = null) {
        return response()->json([
            'success' => true,
            'message' => 'Roles are managed at system level',
        ]);
    });
    
    Route::post('/roles/bulkEdit', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Roles are managed at system level',
        ]);
    });
    
    // ========================
    // PERMISSIONS
    // ========================
    Route::get('/permissions', function () {
        try {
            return response()->json([
                'tour' => [
                    ['id' => 1, 'name' => 'tour.view', 'display_name' => 'View Tours'],
                    ['id' => 2, 'name' => 'tour.create', 'display_name' => 'Create Tours'],
                    ['id' => 3, 'name' => 'tour.edit', 'display_name' => 'Edit Tours'],
                    ['id' => 4, 'name' => 'tour.delete', 'display_name' => 'Delete Tours'],
                ],
                'location' => [
                    ['id' => 5, 'name' => 'location.view', 'display_name' => 'View Destinations'],
                    ['id' => 6, 'name' => 'location.create', 'display_name' => 'Create Destinations'],
                    ['id' => 7, 'name' => 'location.edit', 'display_name' => 'Edit Destinations'],
                    ['id' => 8, 'name' => 'location.delete', 'display_name' => 'Delete Destinations'],
                ],
                'news' => [
                    ['id' => 9, 'name' => 'news.view', 'display_name' => 'View Blog Posts'],
                    ['id' => 10, 'name' => 'news.create', 'display_name' => 'Create Blog Posts'],
                    ['id' => 11, 'name' => 'news.edit', 'display_name' => 'Edit Blog Posts'],
                    ['id' => 12, 'name' => 'news.delete', 'display_name' => 'Delete Blog Posts'],
                ],
                'user' => [
                    ['id' => 13, 'name' => 'user.view', 'display_name' => 'View Users'],
                    ['id' => 14, 'name' => 'user.create', 'display_name' => 'Create Users'],
                    ['id' => 15, 'name' => 'user.edit', 'display_name' => 'Edit Users'],
                    ['id' => 16, 'name' => 'user.delete', 'display_name' => 'Delete Users'],
                ],
                'settings' => [
                    ['id' => 17, 'name' => 'settings.view', 'display_name' => 'View Settings'],
                    ['id' => 18, 'name' => 'settings.edit', 'display_name' => 'Edit Settings'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    Route::post('/roles/{id}/permissions', function (Request $request, $id) {
        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned (simulated)',
        ]);
    });
    
    // ========================
    // STATISTICS
    // ========================
    Route::get('/statistics', function () {
        try {
            $stats = [
                'total' => User::count(),
                'active' => User::where('status', 'publish')->count(),
                'blocked' => User::where('status', 'blocked')->count(),
                'admins' => User::whereIn('role_id', [1, 2])->count(),
                'users' => User::where('role_id', 3)->count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'this_month' => User::whereMonth('created_at', now()->month)->count(),
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // ========================
    // RESET PASSWORD
    // ========================
    Route::post('/resetPassword/{id}', function (Request $request, $id) {
        try {
            $user = User::findOrFail($id);
            
            $newPassword = $request->input('password');
            if (!$newPassword) {
                $newPassword = \Str::random(12);
            }
            
            $user->password = Hash::make($newPassword);
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'password' => $newPassword,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
