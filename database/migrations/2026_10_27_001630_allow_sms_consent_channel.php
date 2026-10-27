<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_consent_records', function (Blueprint $table): void {
            $table->string('channel', 30)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('lead_consent_records')->where('channel', 'SMS')->exists()) {
            throw new RuntimeException('Cannot remove the SMS consent channel while SMS consent history exists.');
        }

        Schema::table('lead_consent_records', function (Blueprint $table): void {
            $table->enum('channel', ['WhatsApp', 'Email', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other'])->change();
        });
    }
};
