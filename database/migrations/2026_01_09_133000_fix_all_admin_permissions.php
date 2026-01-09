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
        $permissions = [
            'location_view',
            'location_create',
            'location_update',
            'location_delete',
            'location_manage_others',
        ];

        // List of potential admin role codes
        $roleCodes = ['administrator', 'admin', 'super_admin'];

        foreach ($roleCodes as $code) {
            $role = DB::table('core_roles')->where('code', $code)->first();

            if ($role) {
                echo "Found Role: {$code} (ID: {$role->id})\n";
                
                foreach ($permissions as $permission) {
                    $exists = DB::table('core_role_permissions')
                        ->where('role_id', $role->id)
                        ->where('permission', $permission)
                        ->exists();

                    if (!$exists) {
                        DB::table('core_role_permissions')->insert([
                            'role_id' => $role->id,
                            'permission' => $permission
                        ]);
                        echo "  - Granted: {$permission}\n";
                    } else {
                        echo "  - Already had: {$permission}\n";
                    }

                    // Clear cache for this permission
                    Cache::forget('role_' . $role->id . '_' . $permission);
                }
            } else {
                echo "Role not found: {$code}\n";
            }
        }
        
        // Final fallback: Clear entire cache if possible to ensure fresh data
        // Cache::flush(); // Be careful with this on production, leaving specific clear.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
