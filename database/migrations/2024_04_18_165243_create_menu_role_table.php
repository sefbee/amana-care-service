<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_role', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('role_id');
            $table->unsignedSmallInteger('menu_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('menu_id')->references('id')->on('menus');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_role');
    }
};
