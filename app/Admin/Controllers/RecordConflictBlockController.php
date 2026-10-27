<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Caregiver;
use App\Admin\Models\Household;
use App\Admin\Models\RecordConflictBlock;
use App\Admin\Models\Role;
use App\Admin\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Admin\Rules\NonClinical;
use Yajra\DataTables\DataTables;

class RecordConflictBlockController extends Controller
{
    public function index(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeManager($request, $access);
        activity('record_conflict_block_access')->causedBy($request->user())
            ->withProperties(['action' => 'index', 'result_count' => RecordConflictBlock::query()->count()])
            ->log('Conflict-of-interest block register viewed');
        $blocks = RecordConflictBlock::query()->with(['staffUser:id,name', 'household:id,display_name', 'caregiver:id,name', 'imposedBy:id,name', 'revokedBy:id,name'])->latest('id');

        if ($request->wantsJson()) {
            return DataTables::of($blocks)
                ->addColumn('staff', fn (RecordConflictBlock $block) => e($block->staffUser?->name ?? '—'))
                ->addColumn('target', fn (RecordConflictBlock $block) => e($block->household?->display_name ?? $block->caregiver?->name ?? 'Deleted record'))
                ->addColumn('record_type', fn (RecordConflictBlock $block) => $block->household_id ? 'Household' : 'Caregiver')
                ->addColumn('state', fn (RecordConflictBlock $block) => $block->revoked_at ? 'Revoked' : 'Active')
                ->addColumn('action', fn (RecordConflictBlock $block) => $block->revoked_at ? '—' : view('admin.crm.conflict-blocks._revoke', compact('block'))->render())
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.crm.conflict-blocks.index', [
            'breadcrumb' => [['title' => 'CRM'], ['title' => 'Conflict-of-Interest Blocks']],
        ]);
    }

    public function create(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeManager($request, $access);
        activity('record_conflict_block_access')->causedBy($request->user())
            ->withProperties(['action' => 'create_form'])
            ->log('Conflict-of-interest block assignment data viewed');
        $staff = User::query()->where('is_active', true)->where('is_platform_superuser', false)
            ->whereHas('roles', fn ($query) => $query->where('roles.is_active', true)->whereNull('roles.deleted_at')->where('role_user.is_active', true))
            ->orderBy('name')->get(['id', 'name', 'userid']);

        return view('admin.crm.conflict-blocks.create', [
            'staff' => $staff,
            'households' => Household::query()->orderBy('display_name')->get(['id', 'display_name', 'area']),
            'caregivers' => Caregiver::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'breadcrumb' => [['title' => 'CRM', 'url' => route('leads.index')], ['title' => 'Conflict-of-Interest Blocks', 'url' => route('record-conflict-blocks.index')], ['title' => 'Add Block']],
        ]);
    }

    public function store(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeManager($request, $access);
        $data = $request->validate([
            'staff_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)->where('is_platform_superuser', false)],
            'target_type' => ['required', Rule::in(['household', 'caregiver'])],
            'household_id' => ['nullable', 'required_if:target_type,household', 'prohibited_unless:target_type,household', 'integer', Rule::exists('households', 'id')->whereNull('deleted_at')],
            'caregiver_id' => ['nullable', 'required_if:target_type,caregiver', 'prohibited_unless:target_type,caregiver', 'integer', Rule::exists('caregivers', 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:10', 'max:500', new NonClinical],
        ]);

        $user = User::query()->whereKey($data['staff_user_id'])->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('roles.is_active', true)->whereNull('roles.deleted_at')->where('role_user.is_active', true))
            ->firstOrFail();
        $householdId = $data['target_type'] === 'household' ? (int) $data['household_id'] : null;
        $caregiverId = $data['target_type'] === 'caregiver' ? (int) $data['caregiver_id'] : null;

        $block = DB::transaction(function () use ($request, $user, $householdId, $caregiverId, $data): RecordConflictBlock {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $existing = RecordConflictBlock::query()->where('staff_user_id', $user->id)->whereNull('revoked_at')
                ->when($householdId, fn ($query) => $query->where('household_id', $householdId))
                ->when($caregiverId, fn ($query) => $query->where('caregiver_id', $caregiverId))->exists();
            if ($existing) {
                return RecordConflictBlock::query()->where('staff_user_id', $user->id)->whereNull('revoked_at')
                    ->when($householdId, fn ($query) => $query->where('household_id', $householdId))
                    ->when($caregiverId, fn ($query) => $query->where('caregiver_id', $caregiverId))->firstOrFail();
            }

            $block = RecordConflictBlock::query()->create([
                'staff_user_id' => $user->id,
                'household_id' => $householdId,
                'caregiver_id' => $caregiverId,
                'imposed_by' => $request->user()->id,
                'reason' => trim($data['reason']),
            ]);
            activity('record_conflict_block')->causedBy($request->user())->performedOn($block)
                ->withProperties(['staff_user_id' => $user->id, 'entity_type' => $householdId ? 'household' : 'caregiver', 'entity_id' => $householdId ?? $caregiverId, 'action' => 'imposed'])
                ->log('Record conflict-of-interest block imposed');

            return $block;
        });

        if (! $block->wasRecentlyCreated) {
            return back()->withErrors(['staff_user_id' => 'An active conflict block already exists for this staff member and record.'])->withInput();
        }

        return redirect()->route('record-conflict-blocks.index')->with('success', 'Conflict block applied. The selected staff member will no longer be able to open this record.');
    }

    public function revoke(Request $request, RecordConflictBlock $block, CrmRecordAccess $access)
    {
        $this->authorizeManager($request, $access);
        $data = $request->validate(['revocation_reason' => ['required', 'string', 'min:10', 'max:500', new NonClinical]]);

        DB::transaction(function () use ($request, $block, $data): void {
            $locked = RecordConflictBlock::query()->lockForUpdate()->findOrFail($block->id);
            abort_if($locked->revoked_at, 422, 'This conflict block has already been revoked.');
            $locked->forceFill(['revoked_at' => now(), 'revoked_by' => $request->user()->id, 'revocation_reason' => trim($data['revocation_reason'])])->save();
            activity('record_conflict_block')->causedBy($request->user())->performedOn($locked)
                ->withProperties(['staff_user_id' => $locked->staff_user_id, 'entity_type' => $locked->household_id ? 'household' : 'caregiver', 'entity_id' => $locked->household_id ?? $locked->caregiver_id, 'action' => 'revoked'])
                ->log('Record conflict-of-interest block revoked');
        });

        return back()->with('success', 'Conflict block revoked with an immutable audit record.');
    }

    private function authorizeManager(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['om', 'platform-superuser'], true), 403);
    }
}
