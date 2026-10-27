<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_proposal_events', function (Blueprint $table): void {
            $table->unsignedInteger('match_proposal_candidate_id')->nullable()->after('match_proposal_id');
            $table->foreign('match_proposal_candidate_id', 'proposal_events_candidate_fk')
                ->references('id')->on('match_proposal_candidates')->restrictOnDelete();
            $table->index(['match_proposal_candidate_id', 'event_type'], 'proposal_candidate_event_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('match_proposal_events', function (Blueprint $table): void {
            $table->dropIndex('proposal_candidate_event_type_idx');
            $table->dropForeign('proposal_events_candidate_fk');
            $table->dropColumn('match_proposal_candidate_id');
        });
    }
};
