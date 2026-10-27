<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safeguarding_incidents', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('classification', 20)->default('Restricted');
            $table->string('category', 80);
            $table->longText('summary');
            $table->longText('details');
            $table->unsignedInteger('household_id')->nullable();
            $table->unsignedInteger('placement_id')->nullable();
            $table->unsignedInteger('caregiver_id')->nullable();
            $table->unsignedInteger('reported_by')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('caregiver_held_at')->nullable();
            $table->unsignedInteger('caregiver_held_by')->nullable();
            $table->timestamps();
            $table->foreign('household_id')->references('id')->on('households')->nullOnDelete();
            $table->foreign('placement_id')->references('id')->on('placements')->nullOnDelete();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('caregiver_held_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['classification', 'created_at']);
            $table->index(['caregiver_id', 'created_at']);
        });

        Schema::create('safeguarding_incident_alerts', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('incident_id');
            $table->unsignedInteger('recipient_id');
            $table->string('priority', 10)->default('High');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->foreign('incident_id')->references('id')->on('safeguarding_incidents')->restrictOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['incident_id', 'recipient_id'], 'safeguarding_alert_recipient_uq');
            $table->index(['recipient_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safeguarding_incident_alerts');
        Schema::dropIfExists('safeguarding_incidents');
    }
};
