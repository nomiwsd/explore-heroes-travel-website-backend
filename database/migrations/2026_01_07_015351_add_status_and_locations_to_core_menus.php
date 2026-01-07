<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('core_menus', function (Blueprint $table) {
            $table->string('status', 50)->default('publish')->after('items');
            $table->json('locations')->nullable()->after('status');
        });
        
        // Update existing menus to have default locations
        \DB::table('core_menus')->where('name', 'LIKE', '%Main%')->update([
            'locations' => json_encode(['header', 'primary'])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_menus', function (Blueprint $table) {
            $table->dropColumn(['status', 'locations']);
        });
    }
};
