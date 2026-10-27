<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_notifications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('notification_type', 80);
            $table->unsignedInteger('recipient_user_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('created_by_user_id')->nullable();
            $table->string('channel', 10);
            $table->string('priority', 10)->default('normal');
            $table->string('idempotency_key', 120)->unique();
            $table->longText('title');
            $table->longText('body');
            $table->string('status', 20)->default('Pending');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('last_error_code', 60)->nullable();
            $table->timestamps();

            $table->foreign('recipient_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'next_attempt_at', 'priority', 'id'], 'operational_notifications_due_idx');
            $table->index(['recipient_user_id', 'channel', 'read_at', 'id'], 'operational_notifications_inbox_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_notifications');
    }
};
