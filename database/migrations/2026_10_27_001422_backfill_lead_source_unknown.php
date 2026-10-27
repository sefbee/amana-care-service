<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leads')
            ->whereNull('source')
            ->update(['source' => 'Unknown — historical source not recorded']);
    }

    public function down(): void
    {
        DB::table('leads')
            ->where('source', 'Unknown — historical source not recorded')
            ->update(['source' => null]);
    }
};
