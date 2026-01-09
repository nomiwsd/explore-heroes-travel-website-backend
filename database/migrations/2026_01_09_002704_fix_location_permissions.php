<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure the 'location_view' permission exists for the Administrator role
        $role = DB::table('core_roles')->where('code', 'administrator')->first();

        if ($role) {
            $permission = 'location_view';
            
            // Check if permission already exists for this role
            $exists = DB::table('core_role_permissions')
                ->where('role_id', $role->id)
                ->where('permission', $permission)
                ->exists();

            if (!$exists) {
                DB::table('core_role_permissions')->insert([
                    'role_id' => $role->id,
                    'permission' => $permission
                ]);
            }

            // 2. Clear the permission cache for this role
            // The cache key format from Role model: 'role_'.$this->id.'_' . $permission
            Cache::forget('role_' . $role->id . '_' . $permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('core_roles')->where('code', 'administrator')->first();

        if ($role) {
            DB::table('core_role_permissions')
                ->where('role_id', $role->id)
                ->where('permission', 'location_view')
                ->delete();
            
            Cache::forget('role_' . $role->id . '_location_view');
        }
    }
};
