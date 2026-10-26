<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }

    public function down(): void
    {
        Schema::create('companies', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('users', function ($table) {
            $table->unsignedInteger('company_id')->nullable();
        });
    }
};
