<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->foreign('open_graph_media_id')->references('id')->on('cms_media')->nullOnDelete();
        });
        Schema::table('cms_media', function (Blueprint $table): void {
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cms_media', function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropForeign(['open_graph_media_id']);
        });
    }
};
