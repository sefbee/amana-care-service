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
        Schema::create('cms_menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('location', ['header', 'footer']);
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('cms_page_id')->nullable();
            $table->string('label', 100);
            $table->string('url', 500);
            $table->boolean('is_external')->default(false);
            $table->boolean('opens_new_tab')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(10);
            $table->foreign('parent_id')->references('id')->on('cms_menu_items')->nullOnDelete();
            $table->foreign('cms_page_id')->references('id')->on('cms_pages')->nullOnDelete();
            $table->index(['location', 'parent_id', 'sort_order']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_menu_items');
    }
};
