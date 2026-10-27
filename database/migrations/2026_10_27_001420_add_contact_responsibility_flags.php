<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('household_contacts', function (Blueprint $table): void {
            $table->boolean('is_primary_decision_maker')->default(false)->after('role');
            $table->boolean('is_billing_contact')->default(false)->after('is_primary_decision_maker');
            $table->boolean('is_emergency_contact')->default(false)->after('is_billing_contact');
        });

        DB::table('household_contacts')->where('role', 'Primary')->update(['is_primary_decision_maker' => true]);
        DB::table('household_contacts')->where('role', 'Billing')->update(['is_billing_contact' => true]);
        DB::table('household_contacts')->where('role', 'Emergency')->update(['is_emergency_contact' => true]);
    }

    public function down(): void
    {
        Schema::table('household_contacts', function (Blueprint $table): void {
            $table->dropColumn(['is_primary_decision_maker', 'is_billing_contact', 'is_emergency_contact']);
        });
    }
};
