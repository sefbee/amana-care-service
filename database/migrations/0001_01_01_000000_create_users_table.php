<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('userid', 40)->unique();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('designation', 255)->nullable();
            $table->rememberToken();

            $table->unsignedSmallInteger('primary_role_id')->nullable()->comment('FK: roles - id');
            $table->unsignedInteger('updated_by');

            $table->boolean('is_password_changed')->default(false)->comment('0 - Not changed, 1 - Changed');
            $table->unsignedTinyInteger('gender')->nullable()->comment('1 - male, 2 - female, 3 - other');
            $table->date('dob')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('phone', 60)->nullable();
            $table->text('photo')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_superuser')->default(false);
            $table->boolean('can_access_admin_panel')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->text('last_update_state')->nullable()->comment('Data difference for last update');

            $table->softDeletes();
            $table->timestamps();

        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
