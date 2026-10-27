<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_superuser') && ! Schema::hasColumn('users', 'is_platform_superuser')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('is_superuser', 'is_platform_superuser');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_platform_superuser') && ! Schema::hasColumn('users', 'is_superuser')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('is_platform_superuser', 'is_superuser');
            });
        }
    }
};
