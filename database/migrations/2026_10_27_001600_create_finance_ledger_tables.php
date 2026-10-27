<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_invoice_sequences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
        DB::table('finance_invoice_sequences')->insert([
            'id' => 1,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('finance_invoices', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('sequence_number')->unique();
            $table->string('invoice_number', 24)->unique();
            $table->unsignedInteger('placement_id');
            $table->unsignedInteger('household_id');
            $table->unsignedInteger('billing_contact_id')->nullable();
            $table->string('billing_contact_name', 150);
            $table->string('billing_contact_email', 254)->nullable();
            $table->string('billing_contact_phone', 20)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('status', 24)->default('Draft');
            $table->unsignedInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->foreign('placement_id')->references('id')->on('placements')->restrictOnDelete();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
            $table->foreign('billing_contact_id')->references('id')->on('household_contacts')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['placement_id', 'period_start', 'period_end'], 'finance_invoice_placement_period_uq');
            $table->index(['status', 'due_date']);
        });

        Schema::create('finance_invoice_lines', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('price_version_id');
            $table->string('description_snapshot', 200);
            $table->decimal('quantity', 8, 2);
            $table->string('unit_snapshot', 50);
            $table->decimal('unit_amount', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->foreign('invoice_id')->references('id')->on('finance_invoices')->restrictOnDelete();
            $table->foreign('price_version_id')->references('id')->on('price_versions')->restrictOnDelete();
        });

        Schema::create('finance_payments', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('receipt_number', 24)->unique();
            $table->unsignedInteger('household_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('payee', 30)->default('Amanah');
            $table->string('method', 30);
            $table->string('reference_id', 150);
            $table->date('paid_at');
            $table->string('status', 20)->default('Unreconciled');
            $table->unsignedInteger('recorded_by')->nullable();
            $table->unsignedInteger('reconciled_by')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->string('bank_statement_reference', 150)->nullable();
            $table->timestamps();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['household_id', 'paid_at']);
            $table->index(['status', 'paid_at']);
        });

        Schema::create('finance_payment_allocations', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('payment_id');
            $table->unsignedInteger('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->foreign('payment_id')->references('id')->on('finance_payments')->restrictOnDelete();
            $table->foreign('invoice_id')->references('id')->on('finance_invoices')->restrictOnDelete();
            $table->unique(['payment_id', 'invoice_id'], 'finance_payment_invoice_uq');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_allocations');
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('finance_invoice_lines');
        Schema::dropIfExists('finance_invoices');
        Schema::dropIfExists('finance_invoice_sequences');
    }
};
