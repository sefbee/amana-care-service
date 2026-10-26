<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name', 100);
            $table->string('phone', 20);
            $table->string('normalized_phone', 20)->index();
            $table->string('email', 254)->nullable()->index();
            $table->enum('channel', ['WhatsApp', 'Email', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other']);
            $table->string('service_interest', 500)->nullable();
            $table->string('area', 100)->nullable();
            $table->enum('live_in_pref', ['live-in', 'live-out', 'either'])->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->decimal('budget_expectation', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['New', 'Contacted', 'Qualified', 'Converted', 'Waitlisted', 'Lost', 'Not Eligible'])->default('New');
            $table->enum('consent_status', ['Unknown', 'Granted', 'Withdrawn'])->default('Unknown');
            $table->string('lost_reason', 100)->nullable();
            $table->string('lost_reason_notes', 500)->nullable();
            $table->unsignedInteger('waitlist_priority')->nullable();
            $table->string('override_reason', 500)->nullable();
            $table->unsignedInteger('household_id')->nullable()->comment('FK: households - id');
            $table->unsignedInteger('owner_id')->nullable()->comment('FK: users - id');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
