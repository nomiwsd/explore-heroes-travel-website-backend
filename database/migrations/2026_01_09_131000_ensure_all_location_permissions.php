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

        // Find Administrator Role
        $role = DB::table('core_roles')->where('code', 'administrator')->first();

        if ($role) {
            foreach ($permissions as $permission) {
                // Check if permission already exists for this role
                $exists = DB::table('core_role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission', $permission)
                    ->exists();

                if (!$exists) {
                    DB::table('core_role_permissions')->insert([
                        'role_id' => $role->id,
                        'permission' => $permission,
                        // 'created_at' => now(), // Assuming standard timestamps if table has them, but core_role_permissions often doesn't. 
                        // If it fails due to missing timestamps, we'll know, but usually pivot tables don't.
                        // Safe to omit if schema unknown, or check migration 1.
                    ]);
                }

                // Clear cache for this permission
                Cache::forget('role_' . $role->id . '_' . $permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We typically don't remove permissions in down() to avoid accidental data loss 
        // if the migration is rolled back but the permissions were wanted.
        // But for completeness:
        /*
        $role = DB::table('core_roles')->where('code', 'administrator')->first();
        if ($role) {
            DB::table('core_role_permissions')
                ->where('role_id', $role->id)
                ->whereIn('permission', ['location_view', 'location_create', 'location_update', 'location_delete', 'location_manage_others'])
                ->delete();
             // Clear cache...
        }
        */
    }
};
