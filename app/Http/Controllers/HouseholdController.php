<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\User;
use App\Rules\NonClinical;
use App\Services\HouseholdService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class HouseholdController extends Controller
{
    public function __construct(protected HouseholdService $householdService) {}

    public function index()
    {
        if (request()->wantsJson()) {
            $households = Household::query()
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
                    $btn .= $row->deleteButton(route('households.destroy', $row->id));
                    return $btn;
                })
                ->rawColumns(['display_name', 'status', 'action'])
                ->make(true);
        }

        $statuses     = config('crm.household_statuses');
        $dhakaZones   = config('crm.dhaka_zones');
        $coordinators = User::active()->get(['id', 'name']);

        return view('households.index', compact('statuses', 'dhakaZones', 'coordinators'));
    }

    public function create()
    {
        $dhakaZones   = config('crm.dhaka_zones');
        $languages    = config('crm.primary_languages');
        $coordinators = User::active()->get(['id', 'name']);

        return view('households.create', compact('dhakaZones', 'languages', 'coordinators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name'            => ['required', 'string', 'min:2', 'max:100'],
            'area'                    => ['required', Rule::in(config('crm.dhaka_zones'))],
            'address_line'            => ['nullable', 'string', 'max:300'],
            'access_notes'            => ['nullable', 'string', 'max:500', new NonClinical],
            'primary_language'        => ['required', Rule::in(config('crm.primary_languages'))],
            'assigned_coordinator_id' => ['required', 'exists:users,id'],
            'founding_cohort'         => ['nullable', 'boolean'],
        ]);

        try {
            $validated['status']     = 'Onboarding';
            $validated['is_active']  = true;
            $validated['created_by'] = auth()->id();

            $household = Household::create($validated);

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
        $household->load(['coordinator', 'contacts', 'careRecipients', 'leads']);
        $contactRoles   = config('crm.contact_roles');
        $recipientTypes = config('crm.recipient_types');
        $ageBands       = config('crm.age_bands');

        return view('households.show', compact('household', 'contactRoles', 'recipientTypes', 'ageBands'));
    }

    public function edit(Household $household)
    {
        $dhakaZones   = config('crm.dhaka_zones');
        $languages    = config('crm.primary_languages');
        $statuses     = config('crm.household_statuses');
        $coordinators = User::active()->get(['id', 'name']);

        return view('households.edit', compact('household', 'dhakaZones', 'languages', 'statuses', 'coordinators'));
    }

    public function update(Request $request, Household $household)
    {
        $validated = $request->validate([
            'display_name'            => ['required', 'string', 'min:2', 'max:100'],
            'area'                    => ['required', Rule::in(config('crm.dhaka_zones'))],
            'address_line'            => ['nullable', 'string', 'max:300'],
            'access_notes'            => ['nullable', 'string', 'max:500', new NonClinical],
            'primary_language'        => ['required', Rule::in(config('crm.primary_languages'))],
            'status'                  => ['required', Rule::in(config('crm.household_statuses'))],
            'assigned_coordinator_id' => ['required', 'exists:users,id'],
            'founding_cohort'         => ['nullable', 'boolean'],
        ]);

        try {
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
            $household = Household::findOrFail($id);
            $household->is_active = $request->toggle_input === 'true' ? 1 : 0;
            $household->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
