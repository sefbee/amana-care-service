<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_conflict_blocks', function (Blueprint $table): void {
            $table->dropUnique('conflict_staff_household_revoked_unique');
            $table->dropUnique('conflict_staff_caregiver_revoked_unique');
            $table->index(['staff_user_id', 'household_id', 'revoked_at'], 'conflict_staff_household_state_idx');
            $table->index(['staff_user_id', 'caregiver_id', 'revoked_at'], 'conflict_staff_caregiver_state_idx');
            $table->text('reason')->change();
            $table->text('revocation_reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('record_conflict_blocks', function (Blueprint $table): void {
            $table->dropIndex('conflict_staff_household_state_idx');
            $table->dropIndex('conflict_staff_caregiver_state_idx');
            $table->unique(['staff_user_id', 'household_id', 'revoked_at'], 'conflict_staff_household_revoked_unique');
            $table->unique(['staff_user_id', 'caregiver_id', 'revoked_at'], 'conflict_staff_caregiver_revoked_unique');
            $table->string('reason', 1024)->change();
            $table->string('revocation_reason', 1024)->nullable()->change();
        });
    }
};
