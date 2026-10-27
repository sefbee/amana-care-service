<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\ServiceRequest;
use App\Admin\Models\TrainingModule;
use App\Admin\Services\CandidateMatcher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    public function candidates(Request $request, ServiceRequest $serviceRequest, CrmRecordAccess $access, CandidateMatcher $matcher)
    {
        $serviceRequest->load(['household', 'service']);
        $access->assertHousehold($request->user(), $serviceRequest->household);
        $mandatoryTrainingIds = TrainingModule::query()->where('is_active', true)->where('is_mandatory', true)->pluck('id');
        $filtered = $matcher->filter($serviceRequest, $mandatoryTrainingIds, actor: $request->user());
        $requiredSkills = $filtered['required_skills'];
        $included = $filtered['included'];
        $excluded = $filtered['excluded'];

        activity('matching_candidate_access')->causedBy($request->user())->performedOn($serviceRequest)
            ->withProperties([
                'household_id' => $serviceRequest->household_id,
                'service_id' => $serviceRequest->service_id,
                'included_candidate_ids' => $included->pluck('id')->all(),
                'excluded_count' => $excluded->count(),
            ])->log('Preliminary candidate filtering run');

        return view('admin.matching.candidates', compact('serviceRequest', 'requiredSkills', 'included', 'excluded'));
    }
}
