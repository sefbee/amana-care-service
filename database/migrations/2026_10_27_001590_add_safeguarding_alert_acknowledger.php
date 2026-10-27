<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safeguarding_incident_alerts', function (Blueprint $table): void {
            $table->unsignedInteger('acknowledged_by')->nullable()->after('acknowledged_at');
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('safeguarding_incident_alerts', function (Blueprint $table): void {
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn('acknowledged_by');
        });
    }
};
