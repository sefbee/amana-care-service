<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_communication_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->comment('FK: leads - id');
            $table->dateTime('contact_date');
            $table->enum('direction', ['Inbound', 'Outbound']);
            $table->enum('channel', ['WhatsApp', 'Email', 'Phone', 'In-Person', 'Other']);
            $table->string('summary', 1000);
            $table->string('next_step', 500)->nullable();
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_communication_logs');
    }
};
