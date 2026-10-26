<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->comment('FK: leads - id');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->unsignedInteger('changed_by')->comment('FK: users - id');

            // No softDeletes — audit table
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_histories');
    }
};
