<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\PlacementFollowUpTask;
use App\Admin\Models\PlacementFollowUpAttempt;
use App\Admin\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class PlacementFollowUpController extends Controller
{
    private function authorizeReadRole(Request $request, CrmRecordAccess $access): string
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['qs', 'om', 'co', 'sl', 'platform-superuser'], true), 403);

        return $role;
    }

    private function authorizeWriteRole(Request $request, CrmRecordAccess $access): string
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['qs', 'platform-superuser'], true), 403);

        return $role;
    }

    private function scopeTasksForActor(Builder $query, User $user, string $role): Builder
    {
        if ($role === 'co') {
            $query->whereHas('placement.serviceRequest', fn (Builder $serviceRequests) => $serviceRequests->where('assigned_coordinator_id', $user->id));
        }

        if ($role !== 'platform-superuser') {
            $query->whereHas('placement', function (Builder $placements) use ($user): void {
                $placements->whereNotExists(function ($blocked) use ($user): void {
                    $blocked->selectRaw('1')->from('record_conflict_blocks')
                        ->where('staff_user_id', $user->id)
                        ->whereNull('revoked_at')
                        ->where(function ($subjects): void {
                            $subjects->whereColumn('record_conflict_blocks.household_id', 'placements.household_id')
                                ->orWhereColumn('record_conflict_blocks.caregiver_id', 'placements.caregiver_id');
                        });
                });
            });
        }

        return $query;
    }

    public function index(Request $request, CrmRecordAccess $access)
    {
        $role = $this->authorizeReadRole($request, $access);
        $query = $this->scopeTasksForActor(PlacementFollowUpTask::query(), $request->user(), $role)
            ->with([
                'placement:id,household_id,caregiver_id,service_request_id',
                'placement.household:id,display_name',
                'placement.caregiver:id,name',
                'latestAttempt:id,follow_up_task_id,outcome',
            ])
            ->orderByRaw("CASE WHEN status = 'Open' THEN 0 ELSE 1 END")
            ->orderBy('due_at');

        if ($request->wantsJson()) {
            activity('placement_follow_up')->causedBy($request->user())
                ->withProperties(['task_ids' => (clone $query)->pluck('id')->all()])
                ->log('Placement follow-up queue accessed');

            return DataTables::of($query)
                ->addColumn('household', fn (PlacementFollowUpTask $task) => e($task->placement->household->display_name ?? 'Household'))
                ->addColumn('caregiver', fn (PlacementFollowUpTask $task) => e($task->placement->caregiver->name ?? 'Caregiver'))
                ->addColumn('milestone', fn (PlacementFollowUpTask $task) => 'Day '.(int) $task->milestone_day)
                ->editColumn('due_at', fn (PlacementFollowUpTask $task) => $task->due_at->format('d M Y'))
                ->addColumn('outcome', fn (PlacementFollowUpTask $task) => e($task->latestAttempt?->outcome ?? '—'))
                ->addColumn('action', fn (PlacementFollowUpTask $task) => '<a class="btn btn-sm btn-light-primary" href="'.e(route('placement-follow-ups.show', $task)).'">Open</a>')
                ->rawColumns(['action'])
                ->make(true);
        }

        activity('placement_follow_up')->causedBy($request->user())
            ->withProperties(['open_count' => (clone $query)->where('status', 'Open')->count()])
            ->log('Placement follow-up queue viewed');

        return view('admin.quality.follow-ups.index', ['breadcrumb' => [['title' => 'Quality & Support'], ['title' => 'Placement Follow-ups']]]);
    }

    public function show(Request $request, PlacementFollowUpTask $task, CrmRecordAccess $access)
    {
        $role = $this->authorizeReadRole($request, $access);
        $task = $this->scopeTasksForActor(PlacementFollowUpTask::query(), $request->user(), $role)
            ->with([
                'placement:id,household_id,caregiver_id,service_request_id',
                'placement.household:id,display_name',
                'placement.caregiver:id,name',
                'attempts:id,follow_up_task_id,attempted_by,attempted_at,outcome,notes',
                'attempts.actor:id,name',
            ])
            ->whereKey($task->id)
            ->firstOrFail();

        activity('placement_follow_up')->causedBy($request->user())->performedOn($task)
            ->withProperties(['placement_id' => $task->placement_id, 'milestone_day' => $task->milestone_day])
            ->log('Placement follow-up record viewed');

        return view('admin.quality.follow-ups.show', [
            'task' => $task,
            'breadcrumb' => [
                ['title' => 'Quality & Support'],
                ['title' => 'Placement Follow-ups', 'url' => route('placement-follow-ups.index')],
                ['title' => 'Day '.(int) $task->milestone_day],
            ],
        ]);
    }

    public function recordAttempt(Request $request, PlacementFollowUpTask $task, CrmRecordAccess $access)
    {
        $role = $this->authorizeWriteRole($request, $access);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['Satisfied', 'Concern', 'Escalate'])],
            'notes' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        DB::transaction(function () use ($request, $task, $data, $role): void {
            $locked = $this->scopeTasksForActor(
                PlacementFollowUpTask::query()->lockForUpdate(),
                $request->user(),
                $role
            )->findOrFail($task->id);
            abort_unless($locked->status === 'Open', 422, 'This follow-up has already been completed.');
            $attempt = PlacementFollowUpAttempt::query()->create([
                'follow_up_task_id' => $locked->id,
                'attempted_by' => $request->user()->id,
                'attempted_at' => now(),
                'outcome' => $data['outcome'],
                'notes' => trim($data['notes']),
            ]);
            $locked->forceFill([
                'status' => 'Completed',
                'completed_by' => $request->user()->id,
                'completed_at' => now(),
            ])->save();

            activity('placement_follow_up')->causedBy($request->user())->performedOn($locked)
                ->withProperties(['placement_id' => $locked->placement_id, 'milestone_day' => $locked->milestone_day, 'attempt_id' => $attempt->id, 'outcome' => $data['outcome']])
                ->log('Placement follow-up attempt recorded');
        });

        return redirect()->route('placement-follow-ups.show', $task)->with('success', 'Follow-up outcome recorded.');
    }
}
