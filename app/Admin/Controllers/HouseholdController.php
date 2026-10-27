<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

use App\Admin\Models\Household;
use App\Admin\Models\HouseholdContact;
use App\Admin\Models\User;
use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Rules\NonClinical;
use App\Admin\Services\HouseholdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class HouseholdController extends Controller
{
    public function __construct(
        protected HouseholdService $householdService,
        protected CrmRecordAccess $recordAccess,
    ) {}

    public function index()
    {
        if (request()->wantsJson()) {
            $households = $this->recordAccess->households(Household::query(), request()->user())
                ->with(['coordinator:id,name'])
                ->withCount('contacts')
                ->when(request()->status, fn ($q) => $q->where('status', request()->status))
                ->when(request()->area, fn ($q) => $q->where('area', request()->area))
                ->when(request()->assigned_coordinator_id, fn ($q) => $q->where('assigned_coordinator_id', request()->assigned_coordinator_id))
                ->when(request()->founding_cohort, fn ($q) => $q->where('founding_cohort', true));

            return DataTables::of($households)
                ->addIndexColumn()
                ->editColumn('display_name', fn ($row) =>
                    '<a href="' . route('households.show', $row->id) . '" class="text-primary fw-bold">' . e($row->display_name) . '</a>'
                )
                ->editColumn('status', fn ($row) => $row->status_badge)
                ->editColumn('coordinator', fn ($row) => $row->coordinator->name ?? '-')
                ->addColumn('contacts_count', fn ($row) => $row->contacts_count)
                ->editColumn('created_at', fn ($row) => $row->created_at->format('d M Y'))
                ->addColumn('action', function ($row) {
                    $btn = $row->editButton(route('households.edit', $row->id));
                    if (auth()->user()->isPlatformSuperuser() || auth()->user()->hasPermission('HouseholdController@destroy')) {
                        $btn .= $row->deleteButton(route('households.destroy', $row->id));
                    }
                    return $btn;
                })
                ->rawColumns(['display_name', 'status', 'action'])
                ->make(true);
        }

        $statuses     = config('crm.household_statuses');
        $dhakaZones   = config('crm.dhaka_zones');
        $coordinators = $this->coordinatorsFor(request()->user());

        return view('admin.crm.households.index', compact('statuses', 'dhakaZones', 'coordinators'));
    }

    public function create()
    {
        $dhakaZones   = config('crm.dhaka_zones');
        $languages    = config('crm.primary_languages');
        $coordinators = $this->coordinatorsFor(request()->user());
        $canViewSensitiveData = $this->recordAccess->canViewSensitiveData(request()->user());

        return view('admin.crm.households.create', compact('dhakaZones', 'languages', 'coordinators', 'canViewSensitiveData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name'            => ['required', 'string', 'min:2', 'max:100'],
            'area'                    => ['required', Rule::in(config('crm.dhaka_zones'))],
            'address_line'            => ['nullable', 'string', 'max:300'],
            'access_notes'            => ['nullable', 'string', 'max:500', new NonClinical],
            'primary_language'        => ['required', Rule::in(config('crm.primary_languages'))],
            'assigned_coordinator_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'founding_cohort'         => ['nullable', 'boolean'],
            'primary_contact_name' => ['required', 'string', 'max:150'],
            'primary_contact_phone' => ['required', 'regex:/^(01[3-9][0-9]{8}|\\+[1-9][0-9]{6,14})$/'],
            'primary_contact_email' => ['nullable', 'email', 'max:254'],
            'billing_same_as_primary' => ['required', 'boolean'],
            'billing_contact_name' => ['required_if:billing_same_as_primary,0', 'nullable', 'string', 'max:150'],
            'billing_contact_phone' => ['required_if:billing_same_as_primary,0', 'nullable', 'regex:/^(01[3-9][0-9]{8}|\\+[1-9][0-9]{6,14})$/'],
            'billing_contact_email' => ['nullable', 'email', 'max:254'],
        ]);
        if (! $this->recordAccess->canViewSensitiveData($request->user())) {
            unset($validated['address_line']);
        }

        try {
            $primaryContact = [
                'name' => $validated['primary_contact_name'],
                'phone' => $validated['primary_contact_phone'],
                'email' => $validated['primary_contact_email'] ?? null,
            ];
            $billingSameAsPrimary = (bool) $validated['billing_same_as_primary'];
            $billingContact = [
                'name' => $validated['billing_contact_name'] ?? null,
                'phone' => $validated['billing_contact_phone'] ?? null,
                'email' => $validated['billing_contact_email'] ?? null,
            ];
            unset($validated['primary_contact_name'], $validated['primary_contact_phone'], $validated['primary_contact_email'], $validated['billing_same_as_primary'], $validated['billing_contact_name'], $validated['billing_contact_phone'], $validated['billing_contact_email']);

            $household = DB::transaction(function () use ($validated, $primaryContact, $billingSameAsPrimary, $billingContact, $request): Household {
                $validated['status'] = 'Onboarding';
                $validated['is_active'] = true;
                $validated['created_by'] = $request->user()->id;
                if (! $this->recordAccess->mayAssignRecords($request->user())) {
                    $validated['assigned_coordinator_id'] = $request->user()->id;
                }

                $household = Household::create($validated);
                HouseholdContact::create($primaryContact + [
                    'household_id' => $household->id,
                    'normalized_phone' => $this->householdService->normalizeContactPhone($primaryContact['phone']),
                    'role' => 'Primary',
                    'is_active' => true,
                    'is_primary_decision_maker' => true,
                    'is_billing_contact' => $billingSameAsPrimary,
                ]);

                if (! $billingSameAsPrimary) {
                    HouseholdContact::create($billingContact + [
                        'household_id' => $household->id,
                        'normalized_phone' => $this->householdService->normalizeContactPhone($billingContact['phone']),
                        'role' => 'Billing',
                        'is_active' => true,
                        'is_primary_decision_maker' => false,
                        'is_billing_contact' => true,
                    ]);
                }

                return $household;
            });

            activity()->causedBy(auth()->user())->performedOn($household)->log('Household created');

            flash()->success('Household created successfully.');
            return redirect()->route('households.show', $household->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function show(Household $household)
    {
        $this->recordAccess->assertHousehold(request()->user(), $household);
        $actor = request()->user();
        $household->load([
            'coordinator',
            'contacts',
            'serviceRequests.service',
            'serviceRequests.quotes.lines.priceVersion',
            'serviceRequests.proposals.candidates.caregiver',
            'serviceRequests.proposals.events',
            'serviceRequests.placements.caregiver',
            'serviceRequests.placements.priceVersion',
            'serviceRequests.proposals.candidates.placement',
            'leads' => fn ($query) => $this->recordAccess->leads($query, $actor),
        ]);
        $visibleCaregiverIds = $this->recordAccess->caregivers(\App\Admin\Models\Caregiver::query(), $actor)->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($household->serviceRequests as $serviceRequest) {
            foreach ($serviceRequest->proposals as $proposal) {
                $visibleCandidates = $proposal->candidates->filter(fn ($candidate) => in_array((int) $candidate->caregiver_id, $visibleCaregiverIds, true))->values();
                $visibleCandidateIds = $visibleCandidates->pluck('id')->map(fn ($id) => (int) $id)->all();
                $proposal->setRelation('candidates', $visibleCandidates);
                $proposal->setRelation('events', $proposal->events->filter(fn ($event) => ! $event->match_proposal_candidate_id || in_array((int) $event->match_proposal_candidate_id, $visibleCandidateIds, true))->values());
            }
            foreach ($serviceRequest->placements as $placement) {
                if (! in_array((int) $placement->caregiver_id, $visibleCaregiverIds, true)) {
                    $placement->setRelation('caregiver', null);
                }
            }
        }
        if ($household->serviceRequests->isNotEmpty()) {
            activity('crm_service_request_access')->causedBy($actor)->performedOn($household)
                ->withProperties(['service_request_ids' => $household->serviceRequests->pluck('id')->all()])
                ->log('Household service request details viewed');
        }
        $restrictedRecipientCount = 0;
        if ($this->recordAccess->canViewSensitiveRecipientData($actor)) {
            $household->load('careRecipients');
            if ($household->careRecipients->where('is_sensitive', true)->isNotEmpty()) {
                activity('sensitive_data_access')
                    ->causedBy(request()->user())
                    ->performedOn($household)
                    ->withProperties([
                        'record_type' => 'CareRecipient',
                        'record_ids' => $household->careRecipients->where('is_sensitive', true)->pluck('id')->all(),
                        'route' => request()->route()?->getName(),
                    ])
                    ->log('Sensitive care recipient data viewed');
            }
        } else {
            $household->setRelation('careRecipients', $household->careRecipients()->where('is_sensitive', false)->get());
            $restrictedRecipientCount = $household->careRecipients()->where('is_sensitive', true)->count();
        }
        $addressLine = null;
        if ($this->recordAccess->canViewSensitiveData($actor)) {
            $addressLine = $household->getRawOriginal('address_line');
            activity('sensitive_data_access')
                ->causedBy($actor)
                ->performedOn($household)
                ->withProperties(['record_type' => 'Household', 'field' => 'address_line', 'route' => request()->route()?->getName()])
                ->log('Sensitive household address viewed');
        }
        $ageBands       = config('crm.age_bands');
        $services = \App\Admin\Models\Service::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.crm.households.show', compact('household', 'ageBands', 'restrictedRecipientCount', 'addressLine', 'services'));
    }

    public function edit(Household $household)
    {
        $this->recordAccess->assertHousehold(request()->user(), $household);
        $canViewSensitiveData = $this->recordAccess->canViewSensitiveData(request()->user());
        $dhakaZones   = config('crm.dhaka_zones');
        $languages    = config('crm.primary_languages');
        $statuses     = config('crm.household_statuses');
        $coordinators = $this->coordinatorsFor(request()->user());

        return view('admin.crm.households.edit', compact('household', 'dhakaZones', 'languages', 'statuses', 'coordinators', 'canViewSensitiveData'));
    }

    public function update(Request $request, Household $household)
    {
        $this->recordAccess->assertHousehold($request->user(), $household);
        $validated = $request->validate([
            'display_name'            => ['required', 'string', 'min:2', 'max:100'],
            'area'                    => ['required', Rule::in(config('crm.dhaka_zones'))],
            'address_line'            => ['nullable', 'string', 'max:300'],
            'access_notes'            => ['nullable', 'string', 'max:500', new NonClinical],
            'primary_language'        => ['required', Rule::in(config('crm.primary_languages'))],
            'status'                  => ['required', Rule::in(config('crm.household_statuses'))],
            'assigned_coordinator_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'founding_cohort'         => ['nullable', 'boolean'],
        ]);

        if (! $this->recordAccess->canViewSensitiveData($request->user())) {
            unset($validated['address_line']);
        }

        try {
            if (! $this->recordAccess->mayAssignRecords($request->user())) {
                $validated['assigned_coordinator_id'] = $household->assigned_coordinator_id;
            }
            $household->update($validated);

            activity()->causedBy(auth()->user())->performedOn($household)->log('Household updated');

            flash()->success('Household updated successfully.');
            return redirect()->route('households.show', $household->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Household $household)
    {
        $this->recordAccess->assertHousehold(request()->user(), $household);

        try {
            activity()->causedBy(auth()->user())->performedOn($household)->log('Household deleted');
            $this->householdService->cascadeSoftDelete($household);
            flash()->success('Household deleted successfully.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete household.');
        }

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $household = $this->recordAccess->households(Household::query(), $request->user())->findOrFail($id);
            $household->is_active = $request->toggle_input === 'true' ? 1 : 0;
            $household->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function coordinatorsFor(User $actor)
    {
        if (! $this->recordAccess->mayAssignRecords($actor)) {
            return User::query()->whereKey($actor->id)->get(['id', 'name']);
        }

        return User::active()->get(['id', 'name']);
    }
}
