<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_communication_logs', function (Blueprint $table): void {
            $table->string('channel', 30)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('lead_communication_logs')->where('channel', 'SMS')->exists()) {
            throw new RuntimeException('Cannot remove the SMS communication channel while SMS log history exists.');
        }

        Schema::table('lead_communication_logs', function (Blueprint $table): void {
            $table->enum('channel', ['WhatsApp', 'Email', 'Phone', 'In-Person', 'Other'])->change();
        });
    }
};
