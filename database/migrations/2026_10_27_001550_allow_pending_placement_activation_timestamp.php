<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table): void {
            $table->timestamp('activated_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (\App\Admin\Models\Placement::query()->whereNull('activated_at')->exists()) {
            throw new RuntimeException('Cannot require activation timestamps while scheduled placements have no activation time.');
        }

        Schema::table('placements', function (Blueprint $table): void {
            $table->timestamp('activated_at')->nullable(false)->change();
        });
    }
};
