<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_proposals', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('service_request_id');
            $table->text('rationale');
            $table->string('status', 30)->default('Draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('service_request_id')->references('id')->on('service_requests')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['service_request_id', 'status']);
        });

        Schema::create('match_proposal_candidates', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('match_proposal_id');
            $table->unsignedInteger('caregiver_id');
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['match_proposal_id', 'caregiver_id'], 'proposal_candidate_once_uq');
            $table->unique(['match_proposal_id', 'position'], 'proposal_candidate_position_uq');
            $table->foreign('match_proposal_id')->references('id')->on('match_proposals')->cascadeOnDelete();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->restrictOnDelete();
        });

        Schema::create('match_proposal_events', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('match_proposal_id');
            $table->string('event_type', 40);
            $table->string('outcome', 40)->nullable();
            $table->text('rationale')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->foreign('match_proposal_id')->references('id')->on('match_proposals')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['match_proposal_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_proposal_events');
        Schema::dropIfExists('match_proposal_candidates');
        Schema::dropIfExists('match_proposals');
    }
};
