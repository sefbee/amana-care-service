<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caregivers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 150);
            $table->date('dob');
            $table->string('gender', 30)->nullable()->comment('Matching preference only; never used as a general demographic filter');
            $table->string('phone', 20);
            $table->string('normalized_phone', 20)->index();
            $table->text('national_id_hash')->comment('Encrypted HMAC hash; never store raw National ID');
            $table->char('national_id_fingerprint', 64)->unique()->comment('Keyed blind index for duplicate detection');
            $table->boolean('live_in_preferred')->default(false);
            $table->date('available_from')->nullable();
            $table->json('zone_preferences')->nullable();
            $table->string('recruitment_stage', 40)->default('New');
            $table->string('status', 40)->default('Not Ready');
            $table->timestamp('safeguarding_hold_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('caregiver_skills', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->string('skill_key', 80);
            $table->boolean('is_proficient')->default(false);
            $table->unsignedInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->unique(['caregiver_id', 'skill_key']);
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
        });

        Schema::create('caregiver_stage_histories', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->string('from_stage', 40)->nullable();
            $table->string('to_stage', 40);
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamps();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
        });

        Schema::create('caregiver_documents', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->enum('type', ['Identity', 'Police', 'Psych']);
            $table->string('storage_disk', 50);
            $table->string('storage_key', 255)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->date('expiry_date')->nullable();
            $table->enum('scan_status', ['Quarantined', 'Scanning', 'Clean', 'Rejected', 'Failed'])->default('Quarantined');
            $table->string('scan_result', 80)->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
            $table->index(['caregiver_id', 'type', 'scan_status']);
        });

        Schema::create('caregiver_screening_checks', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->enum('type', ['Identity', 'Police', 'Psych']);
            $table->enum('outcome', ['Pass', 'Fail', 'Adverse', 'Pending']);
            $table->date('checked_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('evidence_document_id')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
            $table->foreign('evidence_document_id')->references('id')->on('caregiver_documents')->nullOnDelete();
            $table->index(['caregiver_id', 'type', 'outcome', 'valid_until'], 'caregiver_checks_readiness_idx');
        });

        Schema::create('training_modules', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code', 60)->unique();
            $table->string('title', 150);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('caregiver_training_records', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->unsignedInteger('training_module_id');
            $table->enum('outcome', ['Pass', 'Fail', 'Pending']);
            $table->date('attended_at')->nullable();
            $table->unsignedInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->unique(['caregiver_id', 'training_module_id'], 'caregiver_training_once_uq');
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
            $table->foreign('training_module_id')->references('id')->on('training_modules')->restrictOnDelete();
        });

        Schema::create('caregiver_readiness_approvals', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_id');
            $table->boolean('approved');
            $table->string('reason', 500)->nullable();
            $table->unsignedInteger('approved_by');
            $table->timestamp('approved_at');
            $table->timestamps();
            $table->index(['caregiver_id', 'approved_at']);
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->cascadeOnDelete();
        });

        Schema::create('caregiver_document_reveals', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('caregiver_document_id');
            $table->unsignedInteger('actor_id');
            $table->string('reason_code', 80);
            $table->timestamp('attempted_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('result', 30);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('caregiver_document_id')->references('id')->on('caregiver_documents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_document_reveals');
        Schema::dropIfExists('caregiver_readiness_approvals');
        Schema::dropIfExists('caregiver_training_records');
        Schema::dropIfExists('training_modules');
        Schema::dropIfExists('caregiver_screening_checks');
        Schema::dropIfExists('caregiver_documents');
        Schema::dropIfExists('caregiver_stage_histories');
        Schema::dropIfExists('caregiver_skills');
        Schema::dropIfExists('caregivers');
    }
};
