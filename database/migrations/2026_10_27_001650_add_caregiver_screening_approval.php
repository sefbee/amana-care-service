<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caregiver_screening_checks', function (Blueprint $table): void {
            $table->unsignedInteger('approved_by')->nullable()->after('reviewed_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('approval_reason', 500)->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['caregiver_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::table('caregiver_screening_checks', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['caregiver_id', 'approved_at']);
            $table->dropColumn(['approved_by', 'approved_at', 'approval_reason']);
        });
    }
};
