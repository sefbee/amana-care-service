<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('placement_follow_up_tasks')) {
            Schema::create('placement_follow_up_tasks', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('placement_id');
                $table->unsignedTinyInteger('milestone_day');
                $table->timestamp('due_at');
                $table->string('status', 20)->default('Open');
                $table->unsignedInteger('completed_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->foreign('placement_id')->references('id')->on('placements')->restrictOnDelete();
                $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(['placement_id', 'milestone_day'], 'placement_follow_up_milestone_uq');
                $table->index(['status', 'due_at'], 'placement_follow_up_status_due_idx');
            });
        }

        if (! Schema::hasTable('placement_follow_up_attempts')) {
            Schema::create('placement_follow_up_attempts', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('follow_up_task_id');
                $table->unsignedInteger('attempted_by')->nullable();
                $table->timestamp('attempted_at');
                $table->string('outcome', 20);
                $table->longText('notes');
                $table->timestamps();
                $table->foreign('follow_up_task_id')->references('id')->on('placement_follow_up_tasks')->restrictOnDelete();
                $table->foreign('attempted_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['follow_up_task_id', 'attempted_at'], 'placement_follow_up_attempt_task_time_idx');
            });
        } elseif (! Schema::hasIndex('placement_follow_up_attempts', 'placement_follow_up_attempt_task_time_idx')) {
            Schema::table('placement_follow_up_attempts', function (Blueprint $table): void {
                $table->index(['follow_up_task_id', 'attempted_at'], 'placement_follow_up_attempt_task_time_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_follow_up_attempts');
        Schema::dropIfExists('placement_follow_up_tasks');
    }
};
