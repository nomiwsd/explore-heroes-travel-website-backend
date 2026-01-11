<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 100)->unique(); // 'home', 'about', 'faq', 'contact', 'success-stories'
            $table->string('page_title', 255)->nullable();
            $table->json('sections')->nullable(); // All sections data as JSON
            $table->json('draft_sections')->nullable(); // Draft for preview
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_page_settings');
    }
};
