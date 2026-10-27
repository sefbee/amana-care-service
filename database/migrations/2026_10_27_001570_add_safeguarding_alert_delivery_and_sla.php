<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safeguarding_incident_alerts', function (Blueprint $table): void {
            $table->timestamp('sla_breached_at')->nullable()->after('acknowledged_at');
        });

        Schema::create('safeguarding_alert_deliveries', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('incident_alert_id');
            $table->string('channel', 10);
            $table->string('status', 20)->default('Pending');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('last_error_code', 60)->nullable();
            $table->timestamps();
            $table->foreign('incident_alert_id')->references('id')->on('safeguarding_incident_alerts')->restrictOnDelete();
            $table->unique(['incident_alert_id', 'channel'], 'safeguarding_delivery_channel_uq');
            $table->index(['status', 'next_attempt_at'], 'safeguarding_delivery_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safeguarding_alert_deliveries');
        Schema::table('safeguarding_incident_alerts', function (Blueprint $table): void {
            $table->dropColumn('sla_breached_at');
        });
    }
};
