<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Rules\NonClinical;
use App\Services\HouseholdService;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected HouseholdService $householdService
    ) {}

    public function index()
    {
        if (request()->wantsJson()) {
            $leads = Lead::query()
                ->with(['owner:id,name'])
                ->when(request()->status, fn ($q) => $q->where('status', request()->status))
                ->when(request()->channel, fn ($q) => $q->where('channel', request()->channel))
                ->when(request()->area, fn ($q) => $q->where('area', request()->area))
                ->when(request()->owner_id, fn ($q) => $q->where('owner_id', request()->owner_id))
                ->when(request()->date_from, fn ($q) => $q->whereDate('created_at', '>=', request()->date_from))
                ->when(request()->date_to, fn ($q) => $q->whereDate('created_at', '<=', request()->date_to));

            return DataTables::of($leads)
                ->addIndexColumn()
                ->editColumn('full_name', fn ($row) =>
                    '<a href="' . route('leads.show', $row->id) . '" class="text-primary fw-bold">' . e($row->full_name) . '</a>'
                )
                ->editColumn('status', fn ($row) => $row->status_badge)
                ->editColumn('owner', fn ($row) => $row->owner->name ?? '-')
                ->editColumn('created_at', fn ($row) => $row->created_at->format('d M Y'))
                ->addColumn('action', function ($row) {
                    $btn = $row->editButton(route('leads.edit', $row->id));
                    $btn .= $row->deleteButton(route('leads.destroy', $row->id));
                    return $btn;
                })
                ->rawColumns(['full_name', 'status', 'action'])
                ->make(true);
        }

        $statuses     = config('crm.lead_statuses');
        $channels     = config('crm.lead_channels');
        $dhakaZones   = config('crm.dhaka_zones');
        $coordinators = User::active()->get(['id', 'name']);

        return view('leads.index', compact('statuses', 'channels', 'dhakaZones', 'coordinators'));
    }

    public function create()
    {
        $channels     = config('crm.lead_channels');
        $dhakaZones   = config('crm.dhaka_zones');
        $liveInPrefs  = config('crm.live_in_prefs');
        $coordinators = User::active()->get(['id', 'name']);

        return view('leads.create', compact('channels', 'dhakaZones', 'liveInPrefs', 'coordinators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'           => ['required', 'string', 'min:2', 'max:100'],
            'phone'               => ['required', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:254'],
            'channel'             => ['required', Rule::in(config('crm.lead_channels'))],
            'service_interest'    => ['nullable', 'string', 'max:500'],
            'area'                => ['nullable', Rule::in(config('crm.dhaka_zones'))],
            'live_in_pref'        => ['nullable', Rule::in(config('crm.live_in_prefs'))],
            'preferred_start_date'=> ['nullable', 'date'],
            'budget_expectation'  => ['nullable', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string', 'max:2000', new NonClinical],
            'owner_id'            => ['nullable', 'exists:users,id'],
            'override_reason'     => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $validated['normalized_phone'] = $this->leadService->normalizePhone($validated['phone']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // Duplicate detection — if duplicates exist and no override, return warning
        $duplicates = $this->leadService->detectDuplicates(
            $validated['normalized_phone'],
            $validated['email'] ?? null
        );

        if ($duplicates->isNotEmpty() && empty($validated['override_reason'])) {
            return back()
                ->withInput()
                ->with('duplicates', $duplicates)
                ->with('duplicate_warning', true);
        }

        try {
            $validated['created_by'] = auth()->id();
            $validated['status']     = 'New';

            $lead = Lead::create($validated);

            activity()->causedBy(auth()->user())->performedOn($lead)->log('Lead created');

            flash()->success('Lead created successfully.');
            return redirect()->route('leads.show', $lead->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function show(Lead $lead)
    {
        $lead->load(['owner', 'communicationLogs.creator', 'consentRecords.creator', 'statusHistories.changedBy']);
        $logChannels    = config('crm.log_channels');
        $consentStatuses = config('crm.consent_statuses');
        $leadChannels   = config('crm.lead_channels');

        return view('leads.show', compact('lead', 'logChannels', 'consentStatuses', 'leadChannels'));
    }

    public function edit(Lead $lead)
    {
        $channels     = config('crm.lead_channels');
        $dhakaZones   = config('crm.dhaka_zones');
        $liveInPrefs  = config('crm.live_in_prefs');
        $statuses     = config('crm.lead_statuses');
        $lostReasons  = config('crm.lost_reasons');
        $coordinators = User::active()->get(['id', 'name']);

        return view('leads.edit', compact('lead', 'channels', 'dhakaZones', 'liveInPrefs', 'statuses', 'lostReasons', 'coordinators'));
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'full_name'            => ['required', 'string', 'min:2', 'max:100'],
            'phone'                => ['required', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:254'],
            'channel'              => ['required', Rule::in(config('crm.lead_channels'))],
            'service_interest'     => ['nullable', 'string', 'max:500'],
            'area'                 => ['nullable', Rule::in(config('crm.dhaka_zones'))],
            'live_in_pref'         => ['nullable', Rule::in(config('crm.live_in_prefs'))],
            'preferred_start_date' => ['nullable', 'date'],
            'budget_expectation'   => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string', 'max:2000', new NonClinical],
            'owner_id'             => ['nullable', 'exists:users,id'],
            'status'               => ['nullable', Rule::in(config('crm.lead_statuses'))],
            'lost_reason'          => ['nullable', Rule::in(config('crm.lost_reasons'))],
            'lost_reason_notes'    => ['nullable', 'string', 'max:500'],
            'waitlist_priority'    => ['nullable', 'integer', 'min:1'],
            'override_reason'      => ['nullable', 'string', 'max:500'],
        ]);

        // Validate lost reason required
        if (in_array($validated['status'] ?? '', ['Lost', 'Not Eligible']) && empty($validated['lost_reason'])) {
            return back()->withErrors(['lost_reason' => 'A reason is required when marking as Lost or Not Eligible.'])->withInput();
        }

        try {
            $validated['normalized_phone'] = $this->leadService->normalizePhone($validated['phone']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // Status transition validation
        if (isset($validated['status']) && $validated['status'] !== $lead->status) {
            if (!$this->leadService->validateTransition($lead->status, $validated['status'])) {
                return back()->withErrors(['status' => "Cannot transition from {$lead->status} to {$validated['status']}."])->withInput();
            }
        }

        try {
            $oldStatus = $lead->status;

            $lead->update($validated);

            if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
                $this->leadService->recordStatusHistory($lead, $oldStatus, $validated['status']);
            }

            activity()->causedBy(auth()->user())->performedOn($lead)->log('Lead updated');

            flash()->success('Lead updated successfully.');
            return redirect()->route('leads.show', $lead->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Lead $lead)
    {
        try {
            activity()->causedBy(auth()->user())->performedOn($lead)->log('Lead deleted');
            $lead->delete();
            flash()->success('Lead deleted successfully.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete lead.');
        }

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $lead = Lead::findOrFail($id);
            $lead->is_active = $request->toggle_input === 'true' ? 1 : 0;
            $lead->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function convert(Lead $lead)
    {
        if ($lead->status !== 'Qualified') {
            flash()->error('Only Qualified leads can be converted to a Household.');
            return back();
        }

        try {
            $household = $this->householdService->convertFromLead($lead);
            flash()->success('Lead successfully converted to a Household.');
            return redirect()->route('households.show', $household->id);
        } catch (\Exception $e) {
            flash()->error('Conversion failed: ' . $e->getMessage());
            return back();
        }
    }
}
