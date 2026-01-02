<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names', [
            'permissions' => 'permissions',
            'roles' => 'roles',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        $columnNames = config('permission.column_names', [
            'role_pivot_key' => 'role_id',
            'permission_pivot_key' => 'permission_id',
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);

        // Create permissions table if not exists
        if (!Schema::hasTable($tableNames['permissions'])) {
            Schema::create($tableNames['permissions'], function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('display_name')->nullable();
                $table->string('group')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });
        }

        // Create roles table if not exists
        if (!Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('display_name')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });
        }

        // Create model_has_permissions table if not exists
        if (!Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->unsignedBigInteger($columnNames['permission_pivot_key']);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($columnNames['permission_pivot_key'])
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');

                $table->primary([$columnNames['permission_pivot_key'], $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            });
        }

        // Create model_has_roles table if not exists
        if (!Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->unsignedBigInteger($columnNames['role_pivot_key']);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($columnNames['role_pivot_key'])
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');

                $table->primary([$columnNames['role_pivot_key'], $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            });
        }

        // Create role_has_permissions table if not exists
        if (!Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->unsignedBigInteger($columnNames['permission_pivot_key']);
                $table->unsignedBigInteger($columnNames['role_pivot_key']);

                $table->foreign($columnNames['permission_pivot_key'])
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');

                $table->foreign($columnNames['role_pivot_key'])
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');

                $table->primary([$columnNames['permission_pivot_key'], $columnNames['role_pivot_key']], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        // Seed default permissions
        $this->seedDefaultPermissions();
    }

    /**
     * Seed default permissions
     */
    protected function seedDefaultPermissions(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard_access', 'display_name' => 'Access Dashboard', 'group' => 'Dashboard'],

            // Users
            ['name' => 'user_view', 'display_name' => 'View Users', 'group' => 'Users'],
            ['name' => 'user_create', 'display_name' => 'Create Users', 'group' => 'Users'],
            ['name' => 'user_update', 'display_name' => 'Update Users', 'group' => 'Users'],
            ['name' => 'user_delete', 'display_name' => 'Delete Users', 'group' => 'Users'],

            // Roles
            ['name' => 'role_view', 'display_name' => 'View Roles', 'group' => 'Roles'],
            ['name' => 'role_create', 'display_name' => 'Create Roles', 'group' => 'Roles'],
            ['name' => 'role_update', 'display_name' => 'Update Roles', 'group' => 'Roles'],
            ['name' => 'role_delete', 'display_name' => 'Delete Roles', 'group' => 'Roles'],

            // Tours
            ['name' => 'tour_view', 'display_name' => 'View Tours', 'group' => 'Tours'],
            ['name' => 'tour_create', 'display_name' => 'Create Tours', 'group' => 'Tours'],
            ['name' => 'tour_update', 'display_name' => 'Update Tours', 'group' => 'Tours'],
            ['name' => 'tour_delete', 'display_name' => 'Delete Tours', 'group' => 'Tours'],

            // Hotels
            ['name' => 'hotel_view', 'display_name' => 'View Hotels', 'group' => 'Hotels'],
            ['name' => 'hotel_create', 'display_name' => 'Create Hotels', 'group' => 'Hotels'],
            ['name' => 'hotel_update', 'display_name' => 'Update Hotels', 'group' => 'Hotels'],
            ['name' => 'hotel_delete', 'display_name' => 'Delete Hotels', 'group' => 'Hotels'],

            // Bookings
            ['name' => 'booking_view', 'display_name' => 'View Bookings', 'group' => 'Bookings'],
            ['name' => 'booking_manage', 'display_name' => 'Manage Bookings', 'group' => 'Bookings'],

            // Languages
            ['name' => 'language_view', 'display_name' => 'View Languages', 'group' => 'Languages'],
            ['name' => 'language_translation', 'display_name' => 'Manage Translations', 'group' => 'Languages'],

            // Settings
            ['name' => 'settings_view', 'display_name' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'settings_update', 'display_name' => 'Update Settings', 'group' => 'Settings'],

            // Reports
            ['name' => 'report_view', 'display_name' => 'View Reports', 'group' => 'Reports'],

            // Media
            ['name' => 'media_upload', 'display_name' => 'Upload Media', 'group' => 'Media'],
            ['name' => 'media_delete', 'display_name' => 'Delete Media', 'group' => 'Media'],
        ];

        foreach ($permissions as $permission) {
            \DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'group' => $permission['group'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default admin role if not exists
        $adminRole = \DB::table('roles')->where('name', 'administrator')->first();
        if (!$adminRole) {
            $roleId = \DB::table('roles')->insertGetId([
                'name' => 'administrator',
                'display_name' => 'Administrator',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign all permissions to admin role
            $allPermissions = \DB::table('permissions')->pluck('id');
            foreach ($allPermissions as $permissionId) {
                \DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'permissions' => 'permissions',
            'roles' => 'roles',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
