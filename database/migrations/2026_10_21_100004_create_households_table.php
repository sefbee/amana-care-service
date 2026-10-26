<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->increments('id');
            $table->string('display_name', 100);
            $table->string('area', 100);
            $table->text('address_line')->nullable()->comment('Sensitive field');
            $table->string('access_notes', 500)->nullable();
            $table->enum('primary_language', ['Bangla', 'English', 'Other']);
            $table->enum('status', ['Onboarding', 'Active', 'On Hold', 'Closed'])->default('Onboarding');
            $table->boolean('founding_cohort')->default(false);
            $table->unsignedInteger('assigned_coordinator_id')->comment('FK: users - id');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
