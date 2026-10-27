<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safeguarding_incidents', function (Blueprint $table): void {
            $table->text('category')->change();
        });
    }

    public function down(): void
    {
        // Encrypted category values cannot safely fit back into the original short varchar.
    }
};
