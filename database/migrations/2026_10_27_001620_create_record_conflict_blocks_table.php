<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_conflict_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('staff_user_id');
            $table->unsignedInteger('household_id')->nullable();
            $table->unsignedInteger('caregiver_id')->nullable();
            $table->unsignedInteger('imposed_by');
            $table->text('reason');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->unique(['staff_user_id', 'household_id', 'revoked_at'], 'conflict_staff_household_revoked_unique');
            $table->unique(['staff_user_id', 'caregiver_id', 'revoked_at'], 'conflict_staff_caregiver_revoked_unique');
            $table->index(['staff_user_id', 'revoked_at']);
            $table->foreign('staff_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->restrictOnDelete();
            $table->foreign('imposed_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_conflict_blocks');
    }
};
