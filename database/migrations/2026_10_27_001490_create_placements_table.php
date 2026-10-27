<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('service_request_id');
            $table->unsignedInteger('match_proposal_candidate_id')->unique();
            $table->unsignedInteger('household_id');
            $table->unsignedInteger('caregiver_id');
            $table->unsignedInteger('quote_id');
            $table->unsignedInteger('price_version_id');
            $table->decimal('price_amount_snapshot', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('schedule_snapshot');
            $table->string('status', 30)->default('Active');
            $table->string('agreement_disk', 50);
            $table->string('agreement_key', 255)->unique();
            $table->string('agreement_mime', 150);
            $table->unsignedBigInteger('agreement_size_bytes');
            $table->char('agreement_sha256', 64);
            $table->unsignedInteger('activated_by')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->foreign('service_request_id')->references('id')->on('service_requests')->restrictOnDelete();
            $table->foreign('match_proposal_candidate_id')->references('id')->on('match_proposal_candidates')->restrictOnDelete();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->restrictOnDelete();
            $table->foreign('quote_id')->references('id')->on('quotes')->restrictOnDelete();
            $table->foreign('price_version_id')->references('id')->on('price_versions')->restrictOnDelete();
            $table->foreign('activated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['caregiver_id', 'status', 'start_date', 'end_date'], 'placement_conflict_lookup_idx');
            $table->index(['household_id', 'status']);
        });

        Schema::create('placement_schedule_slots', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('placement_id');
            $table->unsignedTinyInteger('weekday');
            $table->unsignedSmallInteger('start_minute');
            $table->unsignedSmallInteger('end_minute');
            $table->timestamps();
            $table->foreign('placement_id')->references('id')->on('placements')->cascadeOnDelete();
            $table->unique(['placement_id', 'weekday', 'start_minute', 'end_minute'], 'placement_weekday_slot_uq');
            $table->index(['weekday', 'start_minute', 'end_minute']);
        });

        Schema::create('placement_events', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('placement_id');
            $table->string('event_type', 40);
            $table->text('rationale')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->foreign('placement_id')->references('id')->on('placements')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['placement_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_events');
        Schema::dropIfExists('placement_schedule_slots');
        Schema::dropIfExists('placements');
    }
};
