<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_consent_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->comment('FK: leads - id');
            $table->enum('channel', ['WhatsApp', 'Email', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other']);
            $table->enum('status', ['Unknown', 'Granted', 'Withdrawn']);
            $table->string('source', 200)->nullable();
            $table->dateTime('consented_at')->nullable();
            $table->dateTime('withdrawn_at')->nullable();
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');

            // No softDeletes — append-only audit table
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_consent_records');
    }
};
