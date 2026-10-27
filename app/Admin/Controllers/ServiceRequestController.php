<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Household;
use App\Admin\Models\Service;
use App\Admin\Models\ServiceRequest;
use App\Admin\Rules\NonClinical;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    public function store(Request $request, Household $household, CrmRecordAccess $access)
    {
        $access->assertHousehold($request->user(), $household);
        $service = Service::query()->whereKey($request->input('service_id'))->where('is_active', true)->firstOrFail();
        $assessmentSchema = config('crm.assessment_profiles.'.$service->code, []);

        $rules = [
            'service_id' => ['required', 'integer', Rule::in([$service->id])],
            'live_in_out' => ['required', Rule::in($service->variant === 'n/a' ? ['live-in', 'live-out', 'either'] : [$service->variant])],
            'schedule' => ['required', 'array:weekdays,start_time,end_time'],
            'schedule.weekdays' => ['required', 'array', 'min:1'],
            'schedule.weekdays.*' => ['required', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'schedule.start_time' => ['required', 'date_format:H:i'],
            'schedule.end_time' => ['required', 'date_format:H:i', 'different:schedule.start_time'],
            'assessment' => ['nullable', 'array:'.implode(',', array_keys($assessmentSchema))],
        ];

        foreach ($assessmentSchema as $key => $definition) {
            $rules['assessment.'.$key] = array_values(array_filter([
                ($definition['required'] ?? false) ? 'required' : 'nullable',
                $definition['type'] === 'integer' ? 'integer' : 'string',
                $definition['type'] === 'integer' ? 'min:1' : 'max:1000',
                $definition['type'] === 'string' ? new NonClinical() : null,
            ]));
        }

        $data = $request->validate($rules);
        $serviceRequest = DB::transaction(function () use ($request, $household, $service, $data): ServiceRequest {
            $lockedHousehold = Household::query()->lockForUpdate()->findOrFail($household->id);

            return ServiceRequest::query()->create([
                'household_id' => $lockedHousehold->id,
                'service_id' => $service->id,
                'assigned_coordinator_id' => $lockedHousehold->assigned_coordinator_id,
                'created_by' => $request->user()->id,
                'live_in_out' => $data['live_in_out'],
                'schedule_json' => $data['schedule'],
                'assessment_json' => $data['assessment'] ?? [],
                'status' => 'Open',
            ]);
        });

        activity('crm_service_request')->causedBy($request->user())->performedOn($serviceRequest)
            ->withProperties(['household_id' => $household->id, 'service_id' => $service->id, 'status' => $serviceRequest->status])
            ->log('Service request created');

        return redirect()->route('households.show', $household)->with('success', 'Service request created.');
    }
}
