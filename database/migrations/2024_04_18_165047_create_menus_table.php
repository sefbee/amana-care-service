<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('title');
            $table->string('route_name')->nullable();
            $table->string('menu_url')->nullable();
            $table->string('menu_icon')->nullable();
            $table->unsignedSmallInteger('menu_order')->default(1);
            $table->unsignedSmallInteger('parent_menu_id')->nullable();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
