<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('household_id');
            $table->unsignedInteger('service_id');
            $table->unsignedInteger('assigned_coordinator_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('live_in_out', ['live-in', 'live-out', 'either']);
            $table->json('schedule_json');
            $table->json('assessment_json')->nullable();
            $table->string('status', 40)->default('Open');
            $table->timestamps();
            $table->foreign('household_id')->references('id')->on('households')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->foreign('assigned_coordinator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['household_id', 'status']);
            $table->index(['service_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
