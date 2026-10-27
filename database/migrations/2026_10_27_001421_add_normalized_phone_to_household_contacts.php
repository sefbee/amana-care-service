<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('household_contacts', function (Blueprint $table): void {
            $table->string('normalized_phone', 20)->nullable()->index()->after('phone');
        });

        DB::table('household_contacts')->select(['id', 'phone'])->orderBy('id')->chunk(250, function ($contacts): void {
            foreach ($contacts as $contact) {
                DB::table('household_contacts')->where('id', $contact->id)->update([
                    'normalized_phone' => $this->normalizePhone((string) $contact->phone),
                ]);
            }
        });
    }

    private function normalizePhone(string $raw): ?string
    {
        $cleaned = preg_replace('/[\s\-()]/', '', $raw);
        if (str_starts_with($cleaned, '+880')) {
            $cleaned = '0'.substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '00880')) {
            $cleaned = '0'.substr($cleaned, 5);
        } elseif (str_starts_with($cleaned, '0088')) {
            $cleaned = substr($cleaned, 4);
        }

        return preg_match('/^01[3-9][0-9]{8}$/', $cleaned)
            ? $cleaned
            : (preg_match('/^\+[1-9][0-9]{6,14}$/', $cleaned) ? $cleaned : null);
    }

    public function down(): void
    {
        Schema::table('household_contacts', function (Blueprint $table): void {
            $table->dropIndex(['normalized_phone']);
            $table->dropColumn('normalized_phone');
        });
    }
};
