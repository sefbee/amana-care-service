<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('service_request_id');
            $table->unsignedInteger('version');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('final_total', 12, 2)->nullable();
            $table->string('status', 30)->default('Draft');
            $table->string('override_reason', 500)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['service_request_id', 'version'], 'quotes_request_version_uq');
            $table->foreign('service_request_id')->references('id')->on('service_requests')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quote_lines', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('quote_id');
            $table->unsignedInteger('service_id');
            $table->unsignedInteger('price_version_id');
            $table->string('description_snapshot', 200);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit_snapshot', 50);
            $table->decimal('unit_amount', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->foreign('quote_id')->references('id')->on('quotes')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->foreign('price_version_id')->references('id')->on('price_versions')->restrictOnDelete();
            $table->index(['service_id', 'price_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
    }
};
