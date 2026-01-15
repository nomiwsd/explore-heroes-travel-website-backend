<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\AdminUserController;
use Modules\User\Controllers\RoleController;

// =====================================================
// USER MANAGEMENT
// =====================================================
Route::prefix('module/user')->middleware(['auth:sanctum'])->group(function () {
    
    // USERS (AdminUserController)
    Route::get('/', [AdminUserController::class, 'index'])->middleware('permission:user_view');
    Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:user_view');
    
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->middleware('permission:user_view');
    Route::get('/edit/{id}', [AdminUserController::class, 'show'])->middleware('permission:user_view'); // Alias
    
    Route::post('/users/store/{id?}', [AdminUserController::class, 'store'])->middleware('permission:user_create'); // simplified middleware, logic handles update vs create
    Route::post('/store/{id?}', [AdminUserController::class, 'store'])->middleware('permission:user_create');
    
    Route::post('/users/bulkEdit', [AdminUserController::class, 'bulkEdit'])->middleware('permission:user_update');
    Route::post('/bulkEdit', [AdminUserController::class, 'bulkEdit'])->middleware('permission:user_update');
    
    Route::post('/users/{id}/security', [AdminUserController::class, 'passwordSecurity'])->middleware('permission:user_update');
    
    // DELETE (Single)
    Route::delete('/{id}', function ($id) {
         // forwarding to bulkEdit logic or separate destroy? 
         // Let's use bulkEdit for consistency or implement destroy in controller
         $request = request();
         $request->merge(['ids' => [$id], 'action' => 'delete']);
         return app(AdminUserController::class)->bulkEdit($request);
    })->middleware('permission:user_delete');


    // ROLES (RoleController)
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role_view');
    Route::get('/roles/{id}', [RoleController::class, 'show'])->middleware('permission:role_view');
    Route::post('/roles/store/{id?}', [RoleController::class, 'store'])->middleware('permission:role_create');
    Route::post('/roles/bulkEdit', [RoleController::class, 'bulkEdit'])->middleware('permission:role_delete');
    
    // PERMISSIONS
    Route::get('/permissions', [RoleController::class, 'getPermissions'])->middleware('permission:role_view');
    
    // STATISTICS (Keep closure or move to controller)
    Route::get('/statistics', function () {
         return response()->json([
            'total' => \App\User::count(),
            'active' => \App\User::where('status', 'publish')->count(),
            'blocked' => \App\User::where('status', 'blocked')->count(),
         ]);
    });
});
