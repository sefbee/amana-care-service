<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Household;
use App\Admin\Models\Placement;
use App\Admin\Models\Role;
use App\Admin\Models\RoutineComplaint;
use App\Admin\Models\RoutineComplaintEvent;
use App\Admin\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class RoutineComplaintController extends Controller
{
    private function authorizeComplaints(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['qs', 'platform-superuser'], true), 403);
    }

    private function qsAssignees()
    {
        return Role::query()->where('slug', 'qs')->first()?->users()
            ->where('users.is_active', true)->wherePivot('is_active', true)
            ->orderBy('users.name')->get(['users.id', 'users.name', 'users.userid']) ?? collect();
    }

    private function assertQsAssignee(int $userId): void
    {
        abort_unless($this->qsAssignees()->contains(fn (User $user) => (int) $user->id === $userId), 422, 'The complaint owner must be an active QS user.');
    }

    public function index(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeComplaints($request, $access);
        $query = RoutineComplaint::query()->with(['household', 'placement', 'assignee'])
            ->orderByRaw("CASE WHEN status IN ('Open', 'In Progress', 'Escalated') THEN 0 ELSE 1 END")
            ->latest('id');

        if ($request->wantsJson()) {
            activity('routine_complaint_access')->causedBy($request->user())
                ->withProperties(['record_count' => (clone $query)->count()])
                ->log('Routine complaint queue accessed');

            return DataTables::of($query)
                ->addColumn('subject', fn (RoutineComplaint $complaint) => e($complaint->subject))
                ->addColumn('household_name', fn (RoutineComplaint $complaint) => e($complaint->household->display_name))
                ->addColumn('placement_ref', fn (RoutineComplaint $complaint) => $complaint->placement ? 'Placement #'.(int) $complaint->placement->id : '—')
                ->addColumn('owner', fn (RoutineComplaint $complaint) => e($complaint->assignee?->name ?? 'Unassigned'))
                ->editColumn('created_at', fn (RoutineComplaint $complaint) => $complaint->created_at->format('d M Y'))
                ->addColumn('action', fn (RoutineComplaint $complaint) => '<a class="btn btn-sm btn-light-primary" href="'.e(route('routine-complaints.show', $complaint)).'">Open</a>')
                ->rawColumns(['action'])
                ->make(true)->header('Cache-Control', 'private, no-store');
        }

        activity('routine_complaint_access')->causedBy($request->user())
            ->withProperties(['record_count' => (clone $query)->count()])
            ->log('Routine complaint queue viewed');

        return view('admin.quality.complaints.index');
    }

    public function create(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeComplaints($request, $access);
        activity('routine_complaint_access')->causedBy($request->user())
            ->withProperties([
                'household_options' => Household::query()->where('is_active', true)->count(),
                'placement_options' => Placement::query()->count(),
            ])->log('Routine complaint intake choices viewed');
        return view('admin.quality.complaints.create', [
            'households' => Household::query()->where('is_active', true)->orderBy('display_name')->get(['id', 'display_name']),
            'placements' => Placement::query()->with('household')->orderByDesc('id')->get(['id', 'household_id', 'status']),
            'assignees' => $this->qsAssignees(),
            'currentRoleSlug' => $access->roleSlug($request->user()),
        ]);
    }

    public function store(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeComplaints($request, $access);
        try {
            $data = $request->validate([
                'household_id' => ['required', 'integer', 'exists:households,id'],
                'placement_id' => ['nullable', 'integer', 'exists:placements,id'],
                'assigned_to' => ['required', 'integer', 'exists:users,id'],
                'subject' => ['required', 'string', 'min:3', 'max:150'],
                'details' => ['required', 'string', 'min:2', 'max:10000'],
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput($request->except(['subject', 'details']));
        }

        $this->assertQsAssignee((int) $data['assigned_to']);
        if (! empty($data['placement_id'])) {
            $placement = Placement::query()->findOrFail($data['placement_id']);
            abort_unless((int) $placement->household_id === (int) $data['household_id'], 422, 'The selected placement does not belong to this household.');
        }

        $complaint = DB::transaction(function () use ($request, $data): RoutineComplaint {
            $this->assertQsAssignee((int) $data['assigned_to']);
            $complaint = RoutineComplaint::query()->create([
                'reference' => 'CMP-'.strtoupper((string) Str::ulid()),
                'household_id' => $data['household_id'],
                'placement_id' => $data['placement_id'] ?? null,
                'reported_by' => $request->user()->id,
                'assigned_to' => $data['assigned_to'],
                'subject' => trim($data['subject']),
                'details' => trim($data['details']),
                'status' => 'Open',
            ]);
            RoutineComplaintEvent::query()->create([
                'complaint_id' => $complaint->id,
                'actor_id' => $request->user()->id,
                'event_type' => 'Logged',
                'to_value' => 'Open',
                'rationale' => null,
                'occurred_at' => now(),
            ]);
            activity('routine_complaint')->causedBy($request->user())->performedOn($complaint)
                ->withProperties(['reference' => $complaint->reference, 'household_id' => $complaint->household_id, 'placement_id' => $complaint->placement_id, 'assigned_to' => $complaint->assigned_to])
                ->log('Routine complaint logged');

            return $complaint;
        });

        return redirect()->route('routine-complaints.show', $complaint)->with('success', 'Routine complaint logged and assigned to QS.');
    }

    public function show(Request $request, RoutineComplaint $complaint, CrmRecordAccess $access)
    {
        $this->authorizeComplaints($request, $access);
        $complaint->load(['household', 'placement', 'reporter', 'assignee', 'resolver', 'events.actor', 'events.previousAssignee', 'events.nextAssignee']);
        activity('routine_complaint_access')->causedBy($request->user())->performedOn($complaint)
            ->withProperties(['reference' => $complaint->reference])
            ->log('Routine complaint detail viewed');

        return view('admin.quality.complaints.show', [
            'complaint' => $complaint,
            'assignees' => $this->qsAssignees(),
        ]);
    }

    public function update(Request $request, RoutineComplaint $complaint, CrmRecordAccess $access)
    {
        $this->authorizeComplaints($request, $access);
        try {
            $data = $request->validate([
                'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
                'status' => ['nullable', Rule::in(RoutineComplaint::STATUSES)],
                'rationale' => ['required', 'string', 'min:2', 'max:4000'],
                'resolution' => ['nullable', 'required_if:status,Resolved', 'string', 'min:2', 'max:4000'],
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput($request->except(['rationale', 'resolution']));
        }
        if (isset($data['assigned_to'])) {
            $this->assertQsAssignee((int) $data['assigned_to']);
        }

        DB::transaction(function () use ($request, $complaint, $data): void {
            $locked = RoutineComplaint::query()->lockForUpdate()->findOrFail($complaint->id);
            $oldAssignee = (int) $locked->assigned_to;
            $oldStatus = $locked->status;
            if (isset($data['assigned_to'])) {
                $this->assertQsAssignee((int) $data['assigned_to']);
            }
            $newAssignee = isset($data['assigned_to']) ? (int) $data['assigned_to'] : (int) $locked->assigned_to;
            $newStatus = $data['status'] ?? $locked->status;
            abort_if($newAssignee === (int) $locked->assigned_to && $newStatus === $locked->status, 422, 'Choose a new owner or status.');

            $allowed = [
                'Open' => ['In Progress', 'Escalated'],
                'In Progress' => ['Escalated', 'Resolved'],
                'Escalated' => ['In Progress', 'Resolved'],
            ];
            abort_if($newStatus !== $locked->status && ! in_array($newStatus, $allowed[$locked->status] ?? [], true), 422, 'This complaint status transition is not allowed.');
            abort_if($locked->status === 'Resolved' && $newAssignee !== (int) $locked->assigned_to, 422, 'Resolved complaints cannot be reassigned.');

            $changes = [];
            if ($newAssignee !== (int) $locked->assigned_to) {
                $changes['assigned_to'] = $newAssignee;
            }
            if ($newStatus !== $locked->status) {
                $changes['status'] = $newStatus;
                if ($newStatus === 'Resolved') {
                    $changes['resolution'] = trim((string) ($data['resolution'] ?? ''));
                    $changes['resolved_by'] = $request->user()->id;
                    $changes['resolved_at'] = now();
                }
            }
            $locked->forceFill($changes)->save();

            if ($newAssignee !== $oldAssignee) {
                RoutineComplaintEvent::query()->create([
                    'complaint_id' => $locked->id,
                    'actor_id' => $request->user()->id,
                    'event_type' => 'Assigned',
                    'from_value' => (string) $oldAssignee,
                    'to_value' => (string) $newAssignee,
                    'rationale' => trim($data['rationale']),
                    'occurred_at' => now(),
                ]);
            }
            if ($newStatus !== $oldStatus) {
                RoutineComplaintEvent::query()->create([
                    'complaint_id' => $locked->id,
                    'actor_id' => $request->user()->id,
                    'event_type' => 'StatusChanged',
                    'from_value' => $oldStatus,
                    'to_value' => $newStatus,
                    'rationale' => trim($data['rationale']),
                    'occurred_at' => now(),
                ]);
            }
            activity('routine_complaint')->causedBy($request->user())->performedOn($locked)
                ->withProperties(['reference' => $locked->reference, 'from_status' => $oldStatus, 'to_status' => $newStatus, 'from_assignee' => $oldAssignee, 'to_assignee' => $newAssignee])
                ->log('Routine complaint updated');
        });

        return redirect()->route('routine-complaints.show', $complaint)->with('success', 'Complaint history updated.');
    }
}
