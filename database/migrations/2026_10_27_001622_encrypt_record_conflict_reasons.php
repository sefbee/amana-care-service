<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_conflict_blocks', function (Blueprint $table): void {
            $table->text('reason')->change();
            $table->text('revocation_reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('record_conflict_blocks', function (Blueprint $table): void {
            $table->string('reason', 1024)->change();
            $table->string('revocation_reason', 1024)->nullable()->change();
        });
    }
};
