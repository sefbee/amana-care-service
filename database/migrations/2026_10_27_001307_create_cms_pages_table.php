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
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 150);
            $table->string('slug', 120)->unique();
            $table->string('published_slug', 120)->nullable()->unique();
            $table->string('meta_title', 60);
            $table->string('meta_description', 160);
            $table->unsignedInteger('open_graph_media_id')->nullable();
            $table->enum('status', ['Draft', 'Published'])->default('Draft');
            $table->json('published_metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('published_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
