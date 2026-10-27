<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Caregiver;
use App\Admin\Models\Household;
use App\Admin\Models\Placement;
use App\Admin\Models\Role;
use App\Admin\Models\SafeguardingIncident;
use App\Admin\Models\SafeguardingIncidentAlert;
use App\Admin\Models\SafeguardingAlertDelivery;
use App\Admin\Models\TrainingModule;
use App\Admin\Services\CaregiverReadinessEvaluator;
use App\Admin\Services\SafeguardingAlertDispatcher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class SafeguardingIncidentController extends Controller
{
    private function mayViewCases(Request $request, CrmRecordAccess $access): bool
    {
        return in_array($access->roleSlug($request->user()), ['om', 'sl', 'platform-superuser'], true);
    }

    private function auditDenied(Request $request, SafeguardingIncident $incident, string $action): void
    {
        activity('safeguarding_access_denied')->causedBy($request->user())->performedOn($incident)
            ->withProperties(['resource' => 'restricted_incident', 'action' => $action, 'result' => 'denied'])
            ->log('Restricted safeguarding access denied');
    }

    public function index(Request $request, CrmRecordAccess $access)
    {
        if (! $this->mayViewCases($request, $access)) {
            activity('safeguarding_access_denied')->causedBy($request->user())
                ->withProperties(['resource' => 'restricted_incident_queue', 'action' => 'index', 'result' => 'denied'])
                ->log('Restricted safeguarding queue access denied');
            abort(403);
        }

        $incidents = SafeguardingIncident::query()->select(['id', 'household_id', 'caregiver_id', 'classification', 'category', 'reported_by', 'created_at'])->with('household:id,display_name')->latest('id');
        if ($request->wantsJson()) {
            $this->auditSensitiveAccess($request, $access, 'queue');

            return DataTables::of($incidents)
                ->addColumn('household', function (SafeguardingIncident $incident) use ($access, $request): string {
                    if (! $incident->household_id) {
                        return '—';
                    }
                    $name = $access->households(Household::query(), $request->user())->whereKey($incident->household_id)->value('display_name');

                    return e($name ?? 'Household details restricted');
                })
                ->addColumn('caregiver', function (SafeguardingIncident $incident) use ($access, $request): string {
                    if (! $incident->caregiver_id) {
                        return '—';
                    }
                    $name = $access->caregivers(Caregiver::query(), $request->user())->whereKey($incident->caregiver_id)->value('name');

                    return e($name ?? 'Caregiver details restricted');
                })
                ->editColumn('created_at', fn (SafeguardingIncident $incident) => $incident->created_at->format('d M Y, h:i A'))
                ->addColumn('action', fn (SafeguardingIncident $incident) => '<a class="btn btn-sm btn-light-danger" href="'.e(route('safeguarding.incidents.show', $incident)).'">Open restricted case</a>')
                ->removeColumn('caregiver_id')
                ->removeColumn('household_id')
                ->rawColumns(['action'])
                ->make(true)->header('Cache-Control', 'private, no-store');
        }

        $this->auditSensitiveAccess($request, $access, 'queue');
        $alertQuery = SafeguardingIncidentAlert::query()->whereNull('acknowledged_at');
        if ($access->roleSlug($request->user()) !== 'platform-superuser') {
            $alertQuery->where('recipient_id', $request->user()->id);
        }
        $alerts = $alertQuery->count();
        $deadLetterQuery = SafeguardingAlertDelivery::query()->where('status', 'DeadLetter')->whereHas('alert', function ($query) use ($request, $access): void {
            if ($access->roleSlug($request->user()) !== 'platform-superuser') {
                $query->where('recipient_id', $request->user()->id);
            }
        });

        return view('admin.quality.incidents.index', [
            'unacknowledgedAlerts' => $alerts,
            'deadLetterCount' => $deadLetterQuery->count(),
            'breadcrumb' => [['title' => 'Quality & Support'], ['title' => 'Restricted Incidents']],
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.quality.incidents.create', ['breadcrumb' => [['title' => 'Quality & Support'], ['title' => 'Report Serious Incident']]]);
    }

    public function store(Request $request, SafeguardingAlertDispatcher $dispatcher)
    {
        try {
            $data = $request->validate([
                'category' => ['required', 'string', 'max:80'],
                'summary' => ['required', 'string', 'min:5', 'max:500'],
                'details' => ['required', 'string', 'min:20', 'max:12000'],
                'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
                'household_id' => ['nullable', 'integer', 'exists:households,id'],
                'placement_id' => ['nullable', 'integer', 'exists:placements,id'],
                'caregiver_id' => ['nullable', 'integer', 'exists:caregivers,id'],
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())
                ->withInput($request->except(['category', 'summary', 'details']));
        }

        $placement = isset($data['placement_id']) ? Placement::query()->findOrFail($data['placement_id']) : null;
        if ($placement) {
            abort_if(isset($data['household_id']) && (int) $data['household_id'] !== (int) $placement->household_id, 422, 'The placement does not belong to the selected household.');
            abort_if(isset($data['caregiver_id']) && (int) $data['caregiver_id'] !== (int) $placement->caregiver_id, 422, 'The caregiver is not assigned to the selected placement.');
            $data['household_id'] = $placement->household_id;
            $data['caregiver_id'] = $placement->caregiver_id;
        }

        $deliveryIds = DB::transaction(function () use ($request, $data): array {
            $incident = SafeguardingIncident::query()->create([
                'category' => trim($data['category']),
                'summary' => trim($data['summary']),
                'details' => trim($data['details']),
                'household_id' => $data['household_id'] ?? null,
                'placement_id' => $data['placement_id'] ?? null,
                'caregiver_id' => $data['caregiver_id'] ?? null,
                'reported_by' => $request->user()->id,
                'occurred_at' => $data['occurred_at'] ?? null,
            ]);

            $safeguardingRole = Role::query()->where('slug', 'sl')->where('is_active', true)->first();
            $recipients = $safeguardingRole?->users()
                ->where('users.is_active', true)
                ->wherePivot('is_active', true)
                ->get() ?? collect();
            $deliveryIds = [];
            foreach ($recipients as $recipient) {
                $alert = SafeguardingIncidentAlert::query()->firstOrCreate(
                    ['incident_id' => $incident->id, 'recipient_id' => $recipient->id],
                    ['priority' => 'High']
                );
                foreach (['email', 'sms'] as $channel) {
                    $delivery = SafeguardingAlertDelivery::query()->firstOrCreate(
                        ['incident_alert_id' => $alert->id, 'channel' => $channel],
                        ['status' => 'Pending', 'next_attempt_at' => now()]
                    );
                    $deliveryIds[] = $delivery->id;
                }
            }

            activity('safeguarding_incident_reported')->causedBy($request->user())->performedOn($incident)
                ->withProperties([
                    'classification' => SafeguardingIncident::CLASSIFICATION_RESTRICTED,
                    'alert_priority' => 'High',
                    'sl_alert_recipients' => $recipients->count(),
                    'has_household' => isset($data['household_id']),
                    'has_placement' => isset($data['placement_id']),
                    'has_caregiver' => isset($data['caregiver_id']),
                ])->log('Restricted serious incident reported');

            return $deliveryIds;
        });

        $dispatcher->run($deliveryIds);

        return redirect()->route('safeguarding.incidents.create')
            ->with('restricted_banner', 'Restricted Case Active - Contact SL');
    }

    public function show(Request $request, SafeguardingIncident $incident, CrmRecordAccess $access)
    {
        if (! $this->mayViewCases($request, $access)) {
            $this->auditDenied($request, $incident, 'show');
            abort(403);
        }

        $this->auditSensitiveAccess($request, $access, 'case_detail', $incident);
        $incident->load(['household', 'placement', 'caregiver', 'reporter', 'caregiverHoldActor']);
        $canAccessCaregiver = ! $incident->caregiver_id || ($incident->caregiver && $access->caregivers(Caregiver::query(), $request->user())->whereKey($incident->caregiver_id)->exists());
        $canAccessHousehold = ! $incident->household_id || ($incident->household && $access->households(Household::query(), $request->user())->whereKey($incident->household_id)->exists());
        if (! $canAccessCaregiver) {
            $incident->setRelation('caregiver', null);
        }
        if (! $canAccessHousehold) {
            $incident->setRelation('household', null);
        }
        $role = $access->roleSlug($request->user());
        $canManageCase = in_array($role, ['sl', 'platform-superuser'], true);
        $alert = null;
        if ($canManageCase) {
            $alertQuery = $incident->alerts();
            if ($role !== 'platform-superuser') {
                $alertQuery->where('recipient_id', $request->user()->id);
            }
            $alert = $alertQuery->whereNull('acknowledged_at')->first()
                ?? ($role === 'platform-superuser'
                    ? $incident->alerts()->latest('id')->first()
                    : $incident->alerts()->where('recipient_id', $request->user()->id)->first());
        }

        $alert?->load(['deliveries', 'acknowledger']);
        return response()->view('admin.quality.incidents.show', [
            'incident' => $incident,
            'canAccessCaregiver' => $canAccessCaregiver,
            'canAccessHousehold' => $canAccessHousehold,
            'canManageCase' => $canManageCase,
            'alert' => $alert,
            'breadcrumb' => [
                ['title' => 'Quality & Support'],
                ['title' => 'Restricted Incidents', 'url' => route('safeguarding.incidents.index')],
                ['title' => 'Restricted Case'],
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function apiShow(Request $request, SafeguardingIncident $incident, CrmRecordAccess $access)
    {
        if (! $this->mayViewCases($request, $access)) {
            $this->auditDenied($request, $incident, 'api_show');
            abort(403);
        }

        $this->auditSensitiveAccess($request, $access, 'api_case_detail', $incident);
        $incident->load(['household', 'caregiver', 'reporter']);
        $caregiverName = $incident->caregiver && $access->caregivers(Caregiver::query(), $request->user())->whereKey($incident->caregiver_id)->exists()
            ? $incident->caregiver->name
            : null;
        $householdName = $incident->household && $access->households(Household::query(), $request->user())->whereKey($incident->household_id)->exists()
            ? $incident->household->display_name
            : null;

        return response()->json([
            'id' => $incident->id,
            'classification' => $incident->classification,
            'category' => $incident->category,
            'summary' => $incident->summary,
            'details' => $incident->details,
            'household' => $householdName,
            'caregiver' => $caregiverName,
            'reporter' => $incident->reporter?->name,
            'created_at' => $incident->created_at?->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function acknowledge(Request $request, SafeguardingIncidentAlert $alert, CrmRecordAccess $access)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['sl', 'platform-superuser'], true), 403);

        DB::transaction(function () use ($request, $alert, $role): void {
            $incidentAlerts = SafeguardingIncidentAlert::query()->where('incident_id', $alert->incident_id)
                ->orderBy('id')->lockForUpdate()->get();
            $locked = $incidentAlerts->firstWhere('id', $alert->id);
            abort_unless($locked, 404);
            abort_unless((int) $locked->recipient_id === (int) $request->user()->id || $role === 'platform-superuser', 403);
            abort_if($locked->acknowledged_at, 422, 'This safeguarding alert has already been acknowledged.');
            $acknowledgedAt = now();
            $pendingAlerts = $incidentAlerts->whereNull('acknowledged_at');
            foreach ($pendingAlerts as $incidentAlert) {
                $incidentAlert->forceFill(['acknowledged_at' => $acknowledgedAt, 'acknowledged_by' => $request->user()->id])->save();
            }
            $cancelledDeliveries = SafeguardingAlertDelivery::query()
                ->whereIn('incident_alert_id', $incidentAlerts->pluck('id'))
                ->whereIn('status', ['Pending', 'Sending'])
                ->update(['status' => 'Cancelled', 'reserved_at' => null, 'next_attempt_at' => null, 'last_error_code' => 'incident_acknowledged']);

            $activity = activity($role === 'platform-superuser' ? 'platform_superuser_break_glass' : 'safeguarding_alert')
                ->causedBy($request->user())->performedOn($locked)
                ->withProperties(['incident_id' => $locked->incident_id, 'priority' => $locked->priority, 'acknowledged_at' => $acknowledgedAt->toIso8601String(), 'recipient_alerts_acknowledged' => $pendingAlerts->count(), 'pending_deliveries_cancelled' => $cancelledDeliveries]);
            $activity->log($role === 'platform-superuser' ? 'Platform Superuser acknowledged restricted safeguarding alert' : 'Safeguarding Lead acknowledged incident alert');
        });

        return redirect()->route('safeguarding.incidents.show', $alert->incident_id)->with('success', 'Alert acknowledgement recorded.');
    }

    public function placeCaregiverOnHold(Request $request, SafeguardingIncident $incident, CrmRecordAccess $access, CaregiverReadinessEvaluator $readiness)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['sl', 'platform-superuser'], true), 403);
        abort_unless($incident->caregiver_id, 422, 'This report is not linked to a caregiver.');
        $access->assertCaregiver($request->user(), Caregiver::query()->findOrFail($incident->caregiver_id));

        DB::transaction(function () use ($request, $incident, $readiness, $role, $access): void {
            $lockedIncident = SafeguardingIncident::query()->lockForUpdate()->findOrFail($incident->id);
            abort_if($lockedIncident->caregiver_held_at, 422, 'This case has already placed the caregiver on hold.');
            $caregiver = Caregiver::query()->lockForUpdate()->findOrFail($lockedIncident->caregiver_id);
            $access->assertCaregiver($request->user(), $caregiver);
            $caregiver->forceFill(['safeguarding_hold_at' => now()])->save();
            $lockedIncident->forceFill(['caregiver_held_at' => now(), 'caregiver_held_by' => $request->user()->id])->save();

            $mandatoryIds = TrainingModule::query()->where('is_active', true)->where('is_mandatory', true)->pluck('id');
            $readiness->refresh($caregiver, $mandatoryIds, (int) $request->user()->id);

            $event = activity($role === 'platform-superuser' ? 'platform_superuser_break_glass' : 'safeguarding_caregiver_hold')
                ->causedBy($request->user())->performedOn($lockedIncident)
                ->withProperties(['incident_id' => $lockedIncident->id, 'caregiver_id' => $caregiver->id, 'readiness' => $caregiver->fresh()->status]);
            $event->log($role === 'platform-superuser' ? 'Platform Superuser applied safeguarding caregiver hold' : 'Safeguarding Lead applied caregiver hold');
        });

        return redirect()->route('safeguarding.incidents.show', $incident)->with('success', 'Caregiver placed On Hold and readiness revoked.');
    }

    private function auditSensitiveAccess(Request $request, CrmRecordAccess $access, string $action, ?SafeguardingIncident $incident = null): void
    {
        $role = $access->roleSlug($request->user());
        $isPsu = $role === 'platform-superuser';
        $activity = activity($isPsu ? 'platform_superuser_break_glass' : 'safeguarding_case_access')
            ->causedBy($request->user());
        if ($incident) {
            $activity->performedOn($incident);
        }
        $activity->withProperties([
            'resource' => 'restricted_incident',
            'action' => $action,
            'incident_id' => $incident?->id,
            'result' => 'allowed',
            'platform_superuser_access' => $isPsu,
        ])->log(match ($role) {
            'platform-superuser' => 'Platform Superuser accessed restricted safeguarding data',
            'om' => 'Operations Manager accessed restricted safeguarding data read-only',
            default => 'Safeguarding Lead accessed restricted case data',
        });
    }
}
