<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('service_id')->comment('FK: services - id');
            $table->decimal('amount', 12, 2);
            $table->string('unit', 50)->default('per month');
            $table->enum('price_type', ['Indicative Starting Price', 'Fixed'])->default('Indicative Starting Price');
            $table->enum('status', ['Draft', 'Approved', 'Retired'])->default('Draft');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_note', 255)->nullable()->comment('e.g. W3 value, Unverified');
            $table->unsignedInteger('created_by')->nullable()->comment('FK: users - id');
            $table->unsignedInteger('approved_by')->nullable()->comment('FK: users - id; must differ from created_by');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_versions');
    }
};
