<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_complaints', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('reference', 30)->unique();
            $table->unsignedInteger('household_id');
            $table->unsignedInteger('placement_id')->nullable();
            $table->unsignedInteger('reported_by');
            $table->unsignedInteger('assigned_to');
            $table->text('subject');
            $table->longText('details');
            $table->string('status', 20)->default('Open');
            $table->longText('resolution')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
            $table->foreign('placement_id')->references('id')->on('placements')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'assigned_to', 'created_at'], 'routine_complaints_queue_idx');
        });

        Schema::create('routine_complaint_events', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('complaint_id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('event_type', 24);
            $table->string('from_value', 150)->nullable();
            $table->string('to_value', 150)->nullable();
            $table->longText('rationale')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->foreign('complaint_id')->references('id')->on('routine_complaints')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['complaint_id', 'occurred_at'], 'routine_complaint_events_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_complaint_events');
        Schema::dropIfExists('routine_complaints');
    }
};
