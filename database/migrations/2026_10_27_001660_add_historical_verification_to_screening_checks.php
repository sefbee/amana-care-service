<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caregivers', function (Blueprint $table): void {
            $table->enum('record_source', ['Operational', 'Historical Import'])
                ->default('Operational')->after('recruitment_stage');
        });

        Schema::table('caregiver_screening_checks', function (Blueprint $table): void {
            $table->enum('verification_status', ['Verified', 'Needs Verification'])
                ->default('Verified')->after('outcome');
            $table->enum('record_source', ['Operational', 'Historical Import'])
                ->default('Operational')->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('caregiver_screening_checks', function (Blueprint $table): void {
            $table->dropColumn(['verification_status', 'record_source']);
        });

        Schema::table('caregivers', function (Blueprint $table): void {
            $table->dropColumn('record_source');
        });
    }
};
