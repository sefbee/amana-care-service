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
        Schema::create('cms_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cms_page_id');
            $table->string('type', 40);
            $table->json('content');
            $table->json('published_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('published_is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(10);
            $table->unsignedSmallInteger('published_sort_order')->default(10);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->foreign('cms_page_id')->references('id')->on('cms_pages')->cascadeOnDelete();
            $table->index(['cms_page_id', 'sort_order']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_sections');
    }
};
