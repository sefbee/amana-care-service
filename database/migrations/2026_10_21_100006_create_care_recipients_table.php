<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('household_id')->comment('FK: households - id');
            $table->string('first_name', 100);
            $table->enum('recipient_type', ['Child', 'Adult', 'Senior']);
            $table->date('date_of_birth')->nullable();
            $table->string('age_band', 20)->nullable();
            $table->string('relationship', 100)->nullable();
            $table->text('support_notes')->nullable()->comment('Non-clinical only');
            $table->text('routines')->nullable();
            $table->string('dietary_restrictions', 500)->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('household_id')->references('id')->on('households')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_recipients');
    }
};
