<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('household_id')->comment('FK: households - id');
            $table->string('name', 150);
            $table->enum('role', ['Primary', 'Billing', 'Emergency']);
            $table->string('phone', 20);
            $table->string('email', 254)->nullable();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('household_id')->references('id')->on('households')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_contacts');
    }
};
