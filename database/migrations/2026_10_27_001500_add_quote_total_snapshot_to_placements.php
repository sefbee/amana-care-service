<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table): void {
            $table->decimal('quote_total_snapshot', 12, 2)->after('price_amount_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table): void {
            $table->dropColumn('quote_total_snapshot');
        });
    }
};
