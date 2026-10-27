<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Caregiver;
use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\CaregiverDocument;
use App\Admin\Models\CaregiverScreeningCheck;
use App\Admin\Models\Role;
use App\Admin\Models\RoleUser;
use App\Admin\Services\CaregiverService;
use App\Admin\Services\CaregiverDocumentVault;
use App\Admin\Services\CaregiverHistoricalImportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class CaregiverController extends Controller
{
    public function index(CrmRecordAccess $access)
    {
        activity('caregiver_sensitive_access')->causedBy(auth()->user())
            ->withProperties(['fields' => ['name', 'phone', 'availability', 'recruitment_stage', 'readiness_status']])
            ->log('Caregiver candidate list viewed');

        if (request()->wantsJson()) {
            $query = $access->caregivers(Caregiver::query(), request()->user())->select(['id', 'name', 'phone', 'recruitment_stage', 'status', 'available_from', 'created_at']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', fn ($row) => '<a class="fw-bold text-primary" href="'.e(route('caregivers.show', $row)).'">'.e($row->name).'</a>')
                ->editColumn('available_from', fn ($row) => $row->available_from?->format('d M Y') ?? '—')
                ->addColumn('action', fn ($row) => '<a class="btn btn-sm btn-light-primary" href="'.e(route('caregivers.show', $row)).'">Open</a>')
                ->rawColumns(['name', 'action'])
                ->make(true);
        }

        return view('admin.workforce.caregivers.index');
    }

    public function create()
    {
        return view('admin.workforce.caregivers.create', [
            'skills' => config('caregivers.skills', []),
            'zones' => config('crm.zones', []),
        ]);
    }

    public function importForm()
    {
        return view('admin.workforce.caregivers.import');
    }

    public function import(Request $request, CaregiverHistoricalImportService $importer)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $importer->import($data['file'], (int) $request->user()->id);

        return redirect()->route('caregivers.import.form')
            ->with('imported_count', $result['imported'])
            ->with('import_errors', array_slice($result['errors'], 0, 100))
            ->with('import_error_count', count($result['errors']));
    }

    public function importTemplate()
    {
        $headers = CaregiverHistoricalImportService::HEADERS;

        return response()->streamDownload(function () use ($headers): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, $headers);
            fclose($output);
        }, 'caregiver-historical-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(Caregiver $caregiver, CrmRecordAccess $access)
    {
        $access->assertCaregiver(request()->user(), $caregiver);
        activity('caregiver_sensitive_access')->causedBy(auth()->user())->performedOn($caregiver)
            ->withProperties(['fields' => ['name', 'dob', 'gender', 'phone', 'availability', 'zones', 'skills']])
            ->log('Caregiver profile edit form viewed');

        $caregiver->load('skills');
        return view('admin.workforce.caregivers.edit', ['caregiver' => $caregiver, 'skills' => config('caregivers.skills', [])]);
    }

    public function update(Request $request, Caregiver $caregiver, CaregiverService $service, CrmRecordAccess $access)
    {
        $access->assertCaregiver($request->user(), $caregiver);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
            'live_in_preferred' => ['sometimes', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'zone_preferences' => ['nullable', 'array'],
            'zone_preferences.*' => ['string', 'max:80'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['boolean'],
        ]);
        $service->update($caregiver, $data, $data['skills'] ?? [], (int) $request->user()->id);

        return redirect()->route('caregivers.show', $caregiver)->with('success', 'Caregiver profile updated. Readiness was re-evaluated.');
    }

    public function store(Request $request, CaregiverService $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
            'national_id' => ['required', 'string', 'max:100'],
            'live_in_preferred' => ['sometimes', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'zone_preferences' => ['nullable', 'array'],
            'zone_preferences.*' => ['string', 'max:80'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['boolean'],
        ]);

        $caregiver = $service->create($validated, $validated['skills'] ?? [], (int) $request->user()->id);

        return redirect()->route('caregivers.show', $caregiver)->with('success', 'Caregiver candidate recorded as Not Ready.');
    }

    public function show(Caregiver $caregiver, CrmRecordAccess $access)
    {
        $access->assertCaregiver(request()->user(), $caregiver);
        activity('caregiver_sensitive_access')->causedBy(auth()->user())->performedOn($caregiver)
            ->withProperties(['fields' => ['name', 'dob', 'gender', 'phone', 'availability', 'zones', 'skills']])
            ->log('Caregiver profile viewed');

        $caregiver->load(['skills', 'stageHistory', 'documents', 'screeningChecks.reviewer', 'screeningChecks.approver', 'trainingRecords.module', 'readinessApprovals.approver']);

        return view('admin.workforce.caregivers.show', [
            'caregiver' => $caregiver,
            'trainingModules' => \App\Admin\Models\TrainingModule::query()->where('is_active', true)->orderBy('title')->get(),
        ]);
    }

    public function recordCheck(Request $request, Caregiver $caregiver, CrmRecordAccess $access)
    {
        $access->assertCaregiver($request->user(), $caregiver);
        $data = $request->validate([
            'type' => ['required', Rule::in(['Identity', 'Police', 'Psych'])],
            'outcome' => ['required', Rule::in(['Pass', 'Fail', 'Adverse', 'Pending'])],
            'checked_at' => ['required', 'date', 'before_or_equal:today'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:checked_at'],
            'evidence_document_id' => ['required', 'integer', 'exists:caregiver_documents,id'],
        ]);

        if ($data['type'] === 'Police' && empty($data['valid_until'])) {
            return back()->withErrors(['valid_until' => 'A police check expiry date is required.']);
        }

        $check = DB::transaction(function () use ($caregiver, $data, $request) {
            $lockedCaregiver = Caregiver::query()->lockForUpdate()->findOrFail($caregiver->id);
            $evidence = $lockedCaregiver->documents()->whereKey($data['evidence_document_id'])->where('scan_status', 'Clean')->lockForUpdate()->firstOrFail();
            if ($evidence->type !== $data['type']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['evidence_document_id' => 'Select clean evidence of the same check type.']);
            }
            if ($evidence->expiry_date && $evidence->expiry_date->lt($data['checked_at'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['evidence_document_id' => 'The selected evidence was expired on the check date.']);
            }
            $check = $lockedCaregiver->screeningChecks()->create($data + ['reviewed_by' => $request->user()->id]);
            app(\App\Admin\Services\CaregiverReadinessEvaluator::class)->refresh($lockedCaregiver, actorId: (int) $request->user()->id);
            activity()->causedBy($request->user())->performedOn($lockedCaregiver)->withProperties(['check_id' => $check->id, 'type' => $check->type, 'outcome' => $check->outcome])->log('Caregiver screening check recorded');
            return $check;
        });

        return back()->with('success', 'Screening result recorded.');
    }

    public function approveScreeningCheck(Request $request, Caregiver $caregiver, CaregiverScreeningCheck $check, CrmRecordAccess $access)
    {
        $user = $request->user();
        $access->assertCaregiver($user, $caregiver);
        $selectedRoleId = session('role_id', $user->primary_role_id);
        $activeRole = Role::query()->whereKey($selectedRoleId)->where('is_active', true)->first();
        $activeAssignment = $activeRole && RoleUser::query()
            ->where('role_id', $activeRole->id)->where('user_id', $user->id)->where('is_active', true)->exists();
        abort_unless($user->isPlatformSuperuser() || ($activeAssignment && in_array($activeRole->slug, ['om', 'sl'], true)), 403);

        $data = $request->validate([
            'approval_reason' => ['required', 'string', 'max:500'],
        ]);
        $reason = trim($data['approval_reason']);
        if ($reason === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'approval_reason' => 'Provide a reason for this screening decision.',
            ]);
        }

        DB::transaction(function () use ($caregiver, $check, $request, $access, $reason): void {
            $lockedCaregiver = Caregiver::query()->lockForUpdate()->findOrFail($caregiver->id);
            $access->assertCaregiver($request->user(), $lockedCaregiver);
            $lockedCheck = $lockedCaregiver->screeningChecks()->whereKey($check->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedCheck->outcome === 'Pending', 422, 'A pending screening result cannot be approved.');
            abort_if($lockedCheck->verification_status !== 'Verified', 422, 'A historical check must be verified with current evidence before approval.');
            abort_if($lockedCheck->approved_at !== null, 409, 'This screening result has already been approved.');

            $lockedCheck->forceFill([
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_reason' => $reason,
            ])->save();
            app(\App\Admin\Services\CaregiverReadinessEvaluator::class)->refresh($lockedCaregiver, actorId: (int) $request->user()->id);
            activity()->causedBy($request->user())->performedOn($lockedCaregiver)->withProperties([
                'check_id' => $lockedCheck->id,
                'type' => $lockedCheck->type,
                'outcome' => $lockedCheck->outcome,
                'reason_length' => mb_strlen($reason),
            ])->log('Caregiver screening result approved');
        });

        return back()->with('success', 'Screening result approved. Readiness was re-evaluated.');
    }

    public function approveReadiness(Request $request, Caregiver $caregiver, CrmRecordAccess $access)
    {
        $access->assertCaregiver($request->user(), $caregiver);
        $data = $request->validate(['approved' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:500']]);
        $ready = DB::transaction(function () use ($caregiver, $data, $request) {
            $lockedCaregiver = Caregiver::query()->lockForUpdate()->findOrFail($caregiver->id);
            $lockedCaregiver->readinessApprovals()->create([
                'approved' => $data['approved'], 'reason' => trim($data['reason']),
                'approved_by' => $request->user()->id, 'approved_at' => now(),
            ]);
            $ready = app(\App\Admin\Services\CaregiverReadinessEvaluator::class)->refresh($lockedCaregiver, actorId: (int) $request->user()->id);
            activity()->causedBy($request->user())->performedOn($lockedCaregiver)->withProperties(['approved' => (bool) $data['approved'], 'ready' => $ready])->log('Caregiver readiness decision recorded');
            return $ready;
        });

        return back()->with('success', $ready ? 'Caregiver is Ready.' : 'Decision recorded; caregiver remains Not Ready until all gates pass.');
    }

    public function storeDocument(Request $request, Caregiver $caregiver, CaregiverDocumentVault $vault, CrmRecordAccess $access)
    {
        $access->assertCaregiver($request->user(), $caregiver);
        $data = $request->validate([
            'type' => ['required', Rule::in(['Identity', 'Police', 'Psych'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        try {
            $document = $vault->upload($caregiver, $data['document'], $data['type'], (int) $request->user()->id);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['document' => 'Secure upload is unavailable. The file was not made accessible.']);
        }

        activity()->causedBy($request->user())->performedOn($caregiver)->withProperties([
            'document_id' => $document->id, 'type' => $document->type, 'scan_status' => $document->scan_status,
        ])->log('Caregiver document uploaded');

        return back()->with($document->scan_status === 'Clean' ? 'success' : 'warning',
            $document->scan_status === 'Clean' ? 'Document scanned and stored in the private vault.' : 'Document remains blocked and cannot be revealed. Scan status: '.$document->scan_status.'.');
    }

    public function revealDocument(Request $request, CaregiverDocument $document, CaregiverDocumentVault $vault, CrmRecordAccess $access)
    {
        $document->loadMissing('caregiver');
        abort_unless($document->caregiver, 404);
        $access->assertCaregiver($request->user(), $document->caregiver);
        $submittedReason = (string) $request->input('reason_code', '');
        $reasonAllowed = array_key_exists($submittedReason, config('caregivers.reveal_reasons', []));
        $reason = $reasonAllowed ? $submittedReason : 'invalid_reason';
        $user = $request->user();
        $selectedRoleId = session('role_id', $user->primary_role_id);
        $activeRole = Role::query()->whereKey($selectedRoleId)->where('is_active', true)->first();
        $activeAssignment = $activeRole && RoleUser::query()
            ->where('role_id', $activeRole->id)->where('user_id', $user->id)->where('is_active', true)->exists();
        $roleAllowed = $user->isPlatformSuperuser() || ($activeAssignment && in_array($activeRole->slug, ['rs', 'om', 'sl'], true));

        $url = $vault->revealUrl($document, (int) $user->id, $reason, $roleAllowed && $reasonAllowed);
        if (! $roleAllowed || ! $reasonAllowed || $url === '') {
            abort(403, 'This document cannot be revealed for the selected reason or account.');
        }

        return redirect()->away($url);
    }
}
