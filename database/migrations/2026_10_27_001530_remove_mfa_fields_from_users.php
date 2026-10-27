<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (['mfa_secret', 'mfa_recovery_codes', 'mfa_enabled_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // MFA secrets and recovery codes are intentionally discarded. Restoring
        // empty columns would reintroduce a feature that has been removed.
    }
};
