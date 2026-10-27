<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_agreement_reveals', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('placement_id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('reason', 500);
            $table->timestamp('attempted_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('result', 30);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('placement_id')->references('id')->on('placements')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['placement_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_agreement_reveals');
    }
};
