<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_consent_records', function (Blueprint $table): void {
            $table->dropForeign(['lead_id']);
            $table->foreign('lead_id')->references('id')->on('leads')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_consent_records', function (Blueprint $table): void {
            $table->dropForeign(['lead_id']);
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }
};
