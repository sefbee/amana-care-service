<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_notifications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('created_by_role_id')->nullable()->after('created_by_user_id');
            $table->foreign('created_by_role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('operational_notifications', function (Blueprint $table): void {
            $table->dropForeign(['created_by_role_id']);
            $table->dropColumn('created_by_role_id');
        });
    }
};
