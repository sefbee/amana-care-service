<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Caregiver;
use App\Admin\Models\TrainingModule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class TrainingRecordController extends Controller
{
    public function index(Request $request, CrmRecordAccess $access)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['tr', 'om', 'platform-superuser'], true), 403);
        $canRecord = in_array($role, ['tr', 'platform-superuser'], true);

        $modules = TrainingModule::query()
            ->where('is_active', true)
            ->where('is_mandatory', true)
            ->orderBy('title')
            ->get(['id', 'code', 'title']);

        $module = null;
        if ($request->filled('training_module_id')) {
            $module = $modules->firstWhere('id', (int) $request->input('training_module_id'));
            abort_unless($module, 404);
        }

        activity('caregiver_sensitive_access')->causedBy($request->user())
            ->withProperties(['view' => 'mandatory_training_roster', 'module_code' => $module?->code])
            ->log('Mandatory training roster viewed');

        if ($request->wantsJson()) {
            $caregivers = Caregiver::query()
                ->when($module, fn ($query) => $query->with(['trainingRecords' => fn ($records) => $records->where('training_module_id', $module->id)]))
                ->when(! $module, fn ($query) => $query->whereRaw('1 = 0'));
            $caregivers = $access->caregivers($caregivers, $request->user())
                ->select(['caregivers.id', 'caregivers.name']);

            return DataTables::of($caregivers)
                ->addIndexColumn()
                ->addColumn('outcome', fn (Caregiver $caregiver) => $caregiver->trainingRecords->first()?->outcome ?? 'Not recorded')
                ->addColumn('attended_at', fn (Caregiver $caregiver) => $caregiver->trainingRecords->first()?->attended_at?->format('d M Y') ?? '—')
                ->addColumn('action', fn (Caregiver $caregiver) => $module && $canRecord ? view('admin.workforce.training._record-form', [
                    'caregiver' => $caregiver,
                    'module' => $module,
                    'record' => $caregiver->trainingRecords->first(),
                ])->render() : ($module ? '—' : 'Select a mandatory module first.'))
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.workforce.training.index', compact('modules', 'module', 'canRecord'));
    }

    public function store(Request $request, Caregiver $caregiver, CrmRecordAccess $access)
    {
        $this->assertTrainerOrPlatformSuperuser($request, $access);
        $access->assertCaregiver($request->user(), $caregiver);

        $data = $request->validate([
            'training_module_id' => ['required', 'integer', Rule::exists('training_modules', 'id')->where(fn ($query) => $query->where('is_active', true)->where('is_mandatory', true))],
            'outcome' => ['required', Rule::in(['Pass', 'Fail', 'Pending'])],
            'attended_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $module = TrainingModule::query()->whereKey($data['training_module_id'])
            ->where('is_active', true)->where('is_mandatory', true)->firstOrFail();

        DB::transaction(function () use ($caregiver, $module, $data, $request, $access): void {
            $lockedCaregiver = Caregiver::query()->lockForUpdate()->findOrFail($caregiver->id);
            $access->assertCaregiver($request->user(), $lockedCaregiver);

            $record = $lockedCaregiver->trainingRecords()->updateOrCreate(
                ['training_module_id' => $module->id],
                ['outcome' => $data['outcome'], 'attended_at' => $data['attended_at'], 'recorded_by' => $request->user()->id]
            );

            app(\App\Admin\Services\CaregiverReadinessEvaluator::class)->refresh($lockedCaregiver, actorId: (int) $request->user()->id);
            activity()->causedBy($request->user())->performedOn($lockedCaregiver)
                ->withProperties(['training_record_id' => $record->id, 'module_code' => $module->code, 'outcome' => $record->outcome])
                ->log('Mandatory caregiver training recorded');
        });

        return redirect()->route('training-records.index', ['training_module_id' => $module->id])
            ->with('success', 'Training attendance and outcome recorded. Readiness was re-evaluated.');
    }

    private function assertTrainerOrPlatformSuperuser(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['tr', 'platform-superuser'], true), 403);
    }
}
