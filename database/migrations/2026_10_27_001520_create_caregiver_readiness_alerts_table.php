<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caregiver_readiness_alerts', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->unsignedInteger('screening_check_id');
            $table->string('alert_type', 30);
            $table->date('valid_until');
            $table->timestamp('read_at')->nullable();
            $table->unsignedInteger('read_by')->nullable();
            $table->timestamps();
            $table->unique(['screening_check_id', 'alert_type'], 'caregiver_check_alert_once_uq');
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->restrictOnDelete();
            $table->foreign('screening_check_id')->references('id')->on('caregiver_screening_checks')->restrictOnDelete();
            $table->foreign('read_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['read_at', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_readiness_alerts');
    }
};
